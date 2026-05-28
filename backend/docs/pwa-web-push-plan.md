# Эпик B — PWA с Web Push

## Контекст для агента, открывшего этот файл «холодным»

Это план поэтапной реализации **независимой фичи**. Параллельно идёт эпик A
(Общий Telegram-чат) в `backend/docs/shared-telegram-chat-plan.md` — он
трогает тот же VO `OwnerContact`, но добавляет другое поле; конфликта по коду
не будет, default `[]` решает merge. Делать можно **в любом порядке**
относительно эпика A.

Перед началом обязательно прочитать:
- `backend/саммари.md` — DDD-карта, шаблоны слоёв, чек-лист «как добавить фичу» (§10).
- `backend/docs/architecture.md` — правила слоёв.
- `backend/docs/owner-panel.md` §4–8 — API карта Owner-панели, рецепт feature-gate, безопасность.
- `frontend/owner/vite.config.ts` — текущая PWA-конфигурация (`vite-plugin-pwa`,
  workbox, manifest, scope `/owner/`).

---

## Цель

Мгновенные push-уведомления о негативных отзывах через PWA — на мобиле и
десктопе (mobile-first). Канал работает параллельно с Telegram. Если Telegram
заблокируют — пуши приходят, функциональность не потеряется.

---

## Архитектурные решения (KISS + SOLID + DDD)

- **Web Push сервер-сайд:** библиотека `minishlink/web-push` (де-факто стандарт
  для PHP). VAPID-ключи — в env.
- **PWA уже сконфигурена** (`vite-plugin-pwa`, autoUpdate, manifest, workbox).
  Нужно переключиться на **`strategies: 'injectManifest'`** + завести
  собственный `src/sw.ts`, чтобы перехватить `push` event. Existing runtime
  caching переносим внутрь нашего SW через workbox-helpers.
- **iOS Safari:** Web Push работает только если PWA установлена на «Домой»
  (iOS 16.4+). UI показывает баннер «Добавить в Home Screen» только в случае
  «iOS + Safari + не standalone»; в остальных случаях — обычная кнопка
  «Включить пуши».
- **Канал доставки** = новый `WebPushNotificationChannel` (instant), встаёт
  рядом с Telegram/Max в `MultiChannelOwnerNotifier::$instantChannels`.
  `supports()` = есть подписки + VAPID сконфигурен. «Никаких throw в канале»
  сохраняем — мёртвые подписки помечаем тихо.
- **Подписка** хранится per-owner; владелец может подписаться с N устройств —
  отправляем по всем активным.
- **Авторизация:** текущая cookie-сессия owner-а (Sanctum). Никаких
  отдельных токенов.
- **Фича-флаг:** рекомендую сделать **бесплатной фичей** (это страховка от
  блокировки TG, не upsell). Если решим монетизировать —
  `Feature::WebPushAlerts = 'web_push_alerts'`.

---

## Фаза B0 — Domain & миграция ✅

- Миграция `owner_push_subscriptions`:
  - `id uuid pk`
  - `owner_id uuid` FK на `users`
  - `endpoint text unique` (URL push-сервиса)
  - `p256dh varchar`
  - `auth varchar`
  - `user_agent varchar nullable`
  - `created_at timestamptz`
  - `last_seen_at timestamptz nullable`
- Domain VO: `App\Domain\Iam\PushSubscriptionEndpoint` (валидация: https URL,
  длина ≤ 2048; `InvalidArgumentException` в конструкторе).
- Малая сущность `App\Domain\Iam\OwnerPushSubscription` (`final`, фабрики
  `register()` / `restore()`).
- Repository-порт `App\Domain\Iam\PushSubscriptionRepository`:
  `save`, `findByEndpoint`, `listByOwner`, `deleteByEndpoint`, `markGone(endpoint)`.
- **Расширить `App\Domain\Notifications\OwnerContact`:**
  - новое поле `public readonly array $pushSubscriptions` (default `[]`),
    элемент — минимум `{ endpoint: string, p256dh: string, auth: string }`
    (заводим VO `PushSubscriptionView` в `App\Domain\Notifications\`);
  - метод `hasPushSubscriptions(): bool`.
- Unit-тесты:
  - `tests/Unit/Domain/Iam/PushSubscriptionEndpointTest.php`;
  - `tests/Unit/Domain/Iam/OwnerPushSubscriptionTest.php`;
  - расширить `tests/Unit/Domain/Notifications/OwnerContactTest.php`.

**Готово, когда:** миграция прогоняется, домен покрыт unit-тестами,
`composer test` зелёный.

---

## Фаза B1 — Application use cases ✅

Папки в `app/Application/Iam/`:

- `RegisterPushSubscription/` — Handler upsert по `endpoint`:
  - если endpoint существует у того же owner-а — обновляем `last_seen_at`;
  - если у другого — переписываем `owner_id` (устройство сменило хозяина);
  - валидация полей через VO.
- `UnregisterPushSubscription/` — удалить по `(owner_id, endpoint)`.
- `ListPushSubscriptionsForOwner/` — Reader для `BuildOwnerContact` (см. ниже).
- `Application/Notifications/Channels/WebPushClient` — **порт** (интерфейс) с
  методом `send(PushSubscriptionView $subscription, string $payload): WebPushSendResult`.
  Результат содержит `delivered: bool` и `gone: bool` (404/410). Это позволит
  мокать в unit-тестах канала.
- Где собирается `OwnerContact`: расширяем `Application\Notifications\BuildOwnerContact`
  (если введён в эпике A) или создаём его здесь. В нём вызываем
  `ListPushSubscriptionsForOwnerHandler`.

Unit-тесты handler'ов с in-memory fake-репозиториями.

---

## Фаза B2 — Infrastructure ✅

- `composer require minishlink/web-push`.
- Eloquent-модель `App\Models\OwnerPushSubscription` + Mapper +
  `EloquentPushSubscriptionRepository` в
  `app/Infrastructure/Persistence/Eloquent/Iam/`.
- Реализация порта: `App\Infrastructure\Notifications\Push\MinishlinkWebPushClient`
  (читает VAPID из `Repository $config`).
- `App\Infrastructure\Notifications\Channels\WebPushNotificationChannel`:
  - зависимости: `WebPushClient`, `PushSubscriptionRepository`, `Repository $config`,
    `LoggerInterface`.
  - `supports($n)` = `vapid configured` && `$n->contact->hasPushSubscriptions()`.
  - `deliver($n)`:
    - формирует payload `{ title, body, url, tag, kind }` (deep-link на
      `/owner/reviews/<reviewId>` если negative_review);
    - в цикле по `$n->contact->pushSubscriptions` шлёт через `WebPushClient`;
    - на `gone=true` зовёт `$repo->markGone($endpoint)` (или `deleteByEndpoint`);
    - **тихо**: ошибки одной подписки не валят всю доставку; throw — только
      если ни одна подписка не доставилась И были хоть какие-то живые
      (это в `MultiChannel` приведёт к попытке fallback). Если все были gone —
      `delivered` = false, но без throw (контракт «без throw в канале»).
- Биндинги в `NotificationsServiceProvider`:
  - `WebPushClient` → `MinishlinkWebPushClient`;
  - `WebPushNotificationChannel` добавить в начало `$instantChannels`.
- Конфиг `config/services.php` (новая секция `webpush`):
  - `public_key`, `private_key`, `subject` из env.
- `.env.example`: `VAPID_PUBLIC_KEY=`, `VAPID_PRIVATE_KEY=`, `VAPID_SUBJECT=mailto:ops@…`.
- Artisan-команда `app/Interface/Console/Commands/GenerateVapidKeysCommand.php`
  (`php artisan webpush:generate-vapid`) — тонкая обёртка над `VAPID::createVapidKeys()`,
  печатает пару в stdout (НЕ пишет в .env автоматически).

---

## Фаза B3 — PWA service worker + frontend ✅

### Service worker

- Переключить `vite-plugin-pwa` на `strategies: 'injectManifest'`,
  `srcDir: 'src'`, `filename: 'sw.ts'`.
- Создать `frontend/owner/src/sw.ts`:
  - `precacheAndRoute(self.__WB_MANIFEST)`;
  - перенести существующие runtime caching правила
    (`owner-me`, `owner-dashboard`) из `vite.config.ts` через
    `registerRoute` + `NetworkFirst`;
  - `self.addEventListener('push', event => { … })`:
    - парсит payload (`event.data?.json()`);
    - `event.waitUntil(self.registration.showNotification(title, { body, icon, badge, tag, data: { url }, requireInteraction: true, vibrate: [200,100,200] }))`;
  - `self.addEventListener('notificationclick', event => { … })`:
    - закрывает уведомление;
    - `event.waitUntil(self.clients.matchAll(...).then(... openWindow(data.url)))` —
      если уже есть открытая вкладка на `/owner/` — focus + postMessage,
      иначе `clients.openWindow`.
- Сохранить `navigateFallback: '/owner/index.html'` и
  `navigateFallbackDenylist` для `/api/`, `/sanctum/`.

### Frontend FSD

- `entities/push-subscription/`:
  - `model/types.ts` — `PushSubscriptionDevice`, `PushConfig`.
  - `api/usePushConfigQuery.ts` (`GET /api/owner/push/config`).
  - `api/useMyPushSubscriptionsQuery.ts`.
  - `config/queryKeys.ts`.
  - `index.ts`.
- `features/enable-push/`:
  - `lib/detectPushSupport.ts` — pure, возвращает
    `{ supported: boolean, requiresIosInstall: boolean }`.
  - `lib/registerPush.ts` — pure async:
    1. `Notification.requestPermission()`.
    2. `navigator.serviceWorker.ready` → `reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey })`.
    3. POST в `/api/owner/push/subscribe`.
  - `lib/unsubscribePush.ts` — обратное (вызвать DELETE и
    `subscription.unsubscribe()`).
  - `api/useEnablePushMutation.ts`, `api/useDisablePushMutation.ts`.
  - `ui/EnablePushButton.tsx`, `ui/IosAddToHomeHint.tsx`,
    `ui/PushPermissionDeniedHint.tsx`.
- `widgets/push-settings-card/` — список устройств + переключатель +
  «Отозвать на этом устройстве».
- Раздел в `pages/profile/` или новая `pages/notifications/`
  (если эпик A создал `pages/notifications/` — переиспользуем).
- **Mobile-first:** bottom-sheet (через `shared/ui/ConfirmDialog` либо новый
  `Sheet` примитив, если в `frontend/shared/ui/` уже есть нечто похожее)
  для prompt'а на телефонах; modal — на десктопе.
- **Permission запрашиваем строго по клику пользователя** (Chrome блокирует
  автоматические prompt'ы). Кнопка «Включить пуши» — в шапке или
  onboarding-баннере.
- vitest на:
  - `lib/detectPushSupport.ts` (детект iOS / Safari / standalone);
  - `lib/registerPush.ts` с моком `navigator.serviceWorker` и `pushManager`;
  - `useEnablePushMutation.ts` с моками `@/shared/api` и `sonner`.

---

## Фаза B4 — Backend routes & feature-тесты ✅

Routes в `routes/api.php` под `auth:owner + tenant + tenant-owns-session`
(подписка не нужна — это бесплатная страховка от блокировки TG):

- `GET /api/owner/push/config` → `{ vapid_public_key: string, enabled: bool }`.
- `GET /api/owner/push/subscriptions` → список устройств.
- `POST /api/owner/push/subscribe` body `{ endpoint, keys: { p256dh, auth }, user_agent? }`
  → `RegisterPushSubscriptionHandler`. 201.
- `DELETE /api/owner/push/subscribe` body `{ endpoint }`
  → `UnregisterPushSubscriptionHandler`. 204.

Тонкие контроллеры в `app/Interface/Http/Controllers/Owner/` (5–10 строк
каждый, по образцу `OwnerProfileController`).

Feature-тесты `tests/Feature/Owner/OwnerPushSubscriptionsTest.php`:
- happy: subscribe → list содержит endpoint;
- 401 без сессии;
- 422 на невалидный endpoint;
- cross-tenant 404 при попытке `DELETE` чужого endpoint (через
  endpoint, принадлежащий другому owner-у — он остаётся, не «угоняется»);
- subscribe + повторный subscribe с тем же endpoint — идемпотентно.

Feature-тест `tests/Feature/Notifications/WebPushDeliveryTest.php`:
- у owner-а 2 активные push-подписки, нет `telegramId`;
- мокаем `WebPushClient` биндингом в контейнере;
- dispatch `SendNegativeReviewAlert` синхронно;
- проверяем 2 вызова `send()`, отсутствие email-fallback;
- ещё один кейс: одна из подписок возвращает `gone=true` → запись удалена из БД.

Unit-тест `WebPushNotificationChannelTest`:
- fake `WebPushClient`, fake `PushSubscriptionRepository`;
- все gone → `delivered=false`, **без throw**;
- mix gone + ok → `delivered=true`, gone-endpoint удалён.

---

## Фаза B5 — Ops, безопасность, документация ✅

- nginx: для `/owner/sw.js` явный `Cache-Control: no-cache, must-revalidate`
  (SW нельзя кешировать долго — иначе пуши не приедут после деплоя).
- `docker/php/Dockerfile`: убедиться, что `composer install` тянет
  `minishlink/web-push` в prod-стадию.
- `.github/workflows/ci.yml`: убедиться, что владельца VAPID-ключей нет
  в commit-history и есть в secrets.
- `docs/DEPLOYMENT.md`: добавить раздел «VAPID keys: генерация и rotation»
  (`php artisan webpush:generate-vapid`, копирование в secrets, rotation —
  старый ключ остаётся, пока активные подписки не пересоздадутся;
  в идеале — отдельный `vapid_key_id` в подписке, но это можно оставить
  на v2).
- `backend/docs/owner-panel.md`:
  - §4 — добавить 4 новых endpoint'а;
  - §5 — новые use cases;
  - §6 — «как добавить новый push-сценарий» (наследник от `negative_review`).
- Если делаем feature-gate: case `Feature::WebPushAlerts = 'web_push_alerts'`
  + строка в `label()` + `feature:web_push_alerts` на subscribe-endpoint;
  тест на 403 без фичи. Иначе — пропускаем.

---

## Чек-лист соответствия принципам

- **SOLID/DDD:** новый канал — отдельный класс, реализующий существующий
  порт `NotificationChannel`. Open/closed: ядро `MultiChannelOwnerNotifier`
  не трогается, добавляется элемент в массив instant-каналов.
  `WebPushClient` — отдельный порт, реализация изолирована, легко мокается.
- **DRY:** payload и runtime caching живут в одном SW-файле; `BuildOwnerContact`
  единый сборщик контакта для всех job'ов (общий с эпиком A).
- **KISS:** ни нового context'а, ни новой очереди, ни новой инфраструктуры
  cron'ов. Дельта = 1 миграция + 1 enum-case (опционально) + 1 канал +
  1 SW-handler + 4 endpoint'а + 1 виджет фронта + 1 composer-пакет.

---

## Подводные камни

- **iOS Web Push требует A2HS** (Add to Home Screen) + iOS 16.4+.
  Пользователю в Safari iOS показываем `IosAddToHomeHint`, а не кнопку.
- **Service worker scope** = `/owner/` (manifest и регистрация — оба!).
  Иначе SW не получит push'и на других путях.
- **`userVisibleOnly: true`** — обязательно: иначе браузеры (Chrome) не
  выдадут endpoint.
- **`applicationServerKey`** должен быть Uint8Array из base64url VAPID
  public key — pure-функция `urlBase64ToUint8Array` в `lib/`.
- **`/sw.js` нельзя кешировать долго** — иначе после деплоя пуши не дойдут.
- **VAPID public key** отдаём через API, не вшиваем в bundle — упрощает
  rotation без передеплоя SPA.
- **Гонка с `vite-plugin-pwa` autoUpdate:** при `injectManifest` workbox
  не подменяет SW автоматически — наш SW сам должен делать `skipWaiting()` +
  клиенты — `Workbox` updateViaCache. Уточнить в фазе B3 на основе текущего
  поведения.
- **CSRF:** `subscribe` — мутация, нужен `ensureCsrf()` (как везде в Owner SPA).

---

## Контракт «готово»

1. `composer test` зелёный (unit + feature).
2. `cd frontend/owner && npm run lint && npm run typecheck && npm run test && npm run build` — без ошибок.
3. `./vendor/bin/pint --test` — без диффа.
4. Ручная проверка на Android Chrome:
   - открыть кабинет → нажать «Включить пуши» → разрешить;
   - сбросить негативный отзыв на тенанте → пуш приходит на устройство;
   - клик по пушу → открывается `/owner/reviews/<id>`.
5. Ручная проверка на iOS Safari (16.4+):
   - до A2HS — показывается hint «Добавить на главный экран»;
   - после A2HS из standalone — кнопка работает, пуш приходит.
6. Ручная проверка с заблокированным Telegram (например, чистый owner без
   `telegramId`): негативный отзыв → пуш приходит, email-fallback НЕ
   срабатывает.
7. Обновлены `backend/docs/owner-panel.md` и `docs/DEPLOYMENT.md`.
