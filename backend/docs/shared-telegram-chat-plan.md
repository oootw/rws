# Эпик A — Общий Telegram-чат на владельца

## Контекст для агента, открывшего этот файл «холодным»

Это план поэтапной реализации **независимой фичи**. Параллельно идёт эпик B
(PWA Web Push) в `backend/docs/pwa-web-push-plan.md` — он трогает тот же VO
`OwnerContact`, но добавляет другое поле; конфликта по коду не будет, default
`[]` решает merge. Делать можно **в любом порядке** относительно эпика B.

Перед началом обязательно прочитать:
- `backend/саммари.md` — DDD-карта, шаблоны слоёв, чек-лист «как добавить фичу» (§10).
- `backend/docs/architecture.md` — правила слоёв.
- `backend/docs/owner-panel.md` §4–6 — API карта Owner-панели, рецепт feature-gate.

---

## Цель

У владельца с несколькими точками появляется опциональный «общий чат» —
Telegram-группа, куда летят все алерты в дополнение к личной ЛС бота.
Несколько админов в одном потоке — могут разобрать негатив быстрее.

---

## Архитектурные решения (KISS + SOLID + DDD)

- **Не плодим новый bounded context.** Добавляем в `Iam` малый агрегат
  `OwnerTelegramChat` (owner ↔ chat_id, многие-к-одному). Привязка — отдельная
  таблица; легче расширить (например, per-place) без миграции домена.
- **`OwnerContact` остаётся immutable VO-«снимком».** Добавляется поле
  `telegramChatIds: list<string>` (default `[]`). DM-id уже есть.
- **Канал не дублируется.** `TelegramNotificationChannel.deliver()` начинает
  рассылать на все targets (DM + group chats). «Доставлено» = хоть один target
  ушёл. Контракт fallback (email только если ни один TG не ушёл) сохраняется.
- **Привязка чата к владельцу — через deep-link токен в `/start` бота**,
  переиспользуем существующую `owner_login_requests`-машину (или создаём
  отдельную таблицу, если миксовать назначения токенов нехорошо для домена —
  решение по факту в фазе A1).
- **Фича-флаг:** новый case `Feature::SharedTelegramChat = 'shared_telegram_chat'`
  (по тарифу, ставится через `feature:<key>` middleware).

---

## Фаза A0 — Domain & миграция ✅ (готово 2026-05-28)

- Миграция `owner_telegram_chats`:
  - `id uuid pk`
  - `owner_id uuid` FK на `users`
  - `chat_id varchar` (Telegram group chat id, может начинаться с `-`)
  - `title varchar nullable`
  - `linked_at timestamptz`
  - `unique(owner_id, chat_id)`
- Domain VO: `App\Domain\Iam\TelegramChatId` — валидирует формат в конструкторе
  (`InvalidArgumentException` при невалидном; группы — отрицательные числа,
  супергруппы — длинные отрицательные; в Application принимаем как string).
- Доменная сущность `App\Domain\Iam\OwnerTelegramChat` — `final`, конструктор
  приватный, фабрики `link()` (с record-событием) и `restore()`. Инвариант
  «уникальность chat_id в рамках owner» обеспечивается репозиторием (см. A2).
- Repository-порт `App\Domain\Iam\OwnerTelegramChatRepository` с методами:
  `save`, `findById`, `listByOwner`, `findByOwnerAndChat`, `delete`.
- **Расширить `App\Domain\Notifications\OwnerContact`:**
  - новое поле `public readonly array $telegramChatIds` (default `[]`);
  - метод `hasAnyTelegramTarget(): bool` = `telegramId !== null || telegramChatIds !== []`.
- Unit-тесты:
  - `tests/Unit/Domain/Iam/TelegramChatIdTest.php` (валидация VO);
  - `tests/Unit/Domain/Iam/OwnerTelegramChatTest.php` (фабрики);
  - расширить `tests/Unit/Domain/Notifications/OwnerContactTest.php`.

**Готово, когда:** мигр прогоняется, домен покрыт unit-тестами, `composer test`
зелёный.

---

## Фаза A1 — Application use cases ✅ (готово 2026-05-28)

**Принятые решения:**

- Под токены deep-link заведена **отдельная таблица** `owner_chat_link_tokens`
  (миграция `2026_05_28_002402_*`) — не миксуем с `owner_login_requests`.
- Token = 32 hex символа (`bin2hex(Randomizer->getBytes(16))`), TTL по умолчанию
  600 сек (env `TELEGRAM_CHAT_LINK_TTL_SECONDS`, ключ
  `guardreviews.chat_link.ttl_seconds`).
- `Owner::asNotificationContact()` оставлен как есть (возвращает пустой
  `telegramChatIds: []`); сборка реальных чатов — в
  `App\Application\Notifications\BuildOwnerContact\BuildOwnerContactHandler`,
  который теперь зовёт `ListOwnerTelegramChatsHandler`. Эпик B уже завёл этот
  handler для push-подписок — фаза A1 только добавила в него зависимость на
  TG-чаты.

**Файлы (для cold-старта на A2):**

- Domain (`app/Domain/Iam/`):
  - `OwnerChatLinkToken` (aggregate, `issue/restore/consume`), `OwnerChatLinkTokenId`,
    `OwnerChatLinkTokenIdGenerator` (port), `OwnerChatLinkTokenRepository` (port).
  - Доменные исключения: `ChatLinkTokenExpired`, `ChatLinkTokenAlreadyConsumed`.
- Application (`app/Application/Iam/`):
  - `IssueTelegramChatLinkToken/` — Command, `IssuedChatLinkToken{deepLink, expiresAt}`, Handler.
    Handler собирает deep-link `https://t.me/{bot_username}?startgroup=<token>`
    из `guardreviews.telegram.bot_username` (env `TELEGRAM_BOT_USERNAME`).
  - `BindTelegramChat/` — Command, Handler. Идёт через `TransactionRunner`,
    публикует `OwnerTelegramChatLinked` через `DomainEventDispatcher`.
    Идемпотентен: при существующей `(owner, chat)` — `rename()` без события.
  - `ListOwnerTelegramChats/` — Query, Handler, `OwnerTelegramChatView{id, chatId, title, linkedAt}`.
  - `UnlinkTelegramChat/` — Command, Handler. Guard: бросает
    `App\Application\Iam\Exceptions\TelegramChatNotOwnedByCaller` если строка
    не принадлежит caller-у (HTTP-слой переводит в 404).
  - Application-исключения в `app/Application/Iam/Exceptions/`:
    `ChatLinkTokenNotFound`, `TelegramChatNotOwnedByCaller`.
- Notifications:
  - `App\Application\Notifications\BuildOwnerContact\BuildOwnerContactHandler`
    зависит от трёх handler'ов: `OwnerRepository`,
    `ListPushSubscriptionsForOwnerHandler`, `ListOwnerTelegramChatsHandler`.
- Tests:
  - `tests/Helpers/iamTelegramChats.php` — fake-репы (`OwnerTelegramChatRepository`,
    `OwnerChatLinkTokenRepository`), fake-генераторы id, `passThroughTransactionRunner`,
    `collectingDomainEventDispatcher`. Зарегистрирован в `tests/Pest.php`.
  - `tests/Unit/Domain/Iam/OwnerChatLinkTokenTest.php` (7 кейсов).
  - `tests/Unit/Application/Iam/TelegramChatHandlersTest.php` (11 кейсов на 4 handler'а).
  - `tests/Unit/Application/Notifications/BuildOwnerContactHandlerTest.php` — расширен.

**Что ждёт фазу A2 (Infrastructure):**

- В Application уже всё зарезолвлено через порты, но **биндингов в DI пока нет**.
  `IamServiceProvider` должен зарегистрировать `Eloquent*Repository` +
  `*IdGenerator` для `OwnerTelegramChat` и `OwnerChatLinkToken`.
- Eloquent-`findActiveByToken` должен фильтровать `consumed_at IS NULL AND expires_at > NOW()`
  (без `WHERE deleted_at` — soft delete не используем).
- `EloquentOwnerTelegramChatRepository::save()` обязан полагаться на
  unique-индекс `(owner_id, chat_id)` — в handler'е уже есть idempotency через
  `findByOwnerAndChat`, но миграция с unique даёт защиту от race-condition.

---

## Фаза A2 — Infrastructure ✅ (готово 2026-05-28)

**Что сделано:**

- Eloquent-модели:
  - `App\Models\OwnerTelegramChat` (UUID PK, fillable, cast `linked_at`,
    `public $timestamps = false`).
  - `App\Models\OwnerChatLinkToken` (UUID PK, fillable, casts
    `expires_at/consumed_at/created_at`, `UPDATED_AT = null`).
- Persistence слой в `app/Infrastructure/Persistence/Eloquent/Iam/`:
  - `OwnerTelegramChatMapper` ↔ `EloquentOwnerTelegramChatRepository`
    (`listByOwner` сортирует по `linked_at`).
  - `OwnerChatLinkTokenMapper` ↔ `EloquentOwnerChatLinkTokenRepository`
    (`findActiveByToken` фильтрует `consumed_at IS NULL` + `expires_at > Clock::now()`).
  - `UuidOwnerTelegramChatIdGenerator`, `UuidOwnerChatLinkTokenIdGenerator`.
- Биндинги в `IamServiceProvider::$bindings`: 4 новых пары
  (репозитории и id-генераторы для `OwnerTelegramChat` и `OwnerChatLinkToken`).
- `App\Jobs\SendNegativeReviewAlert` уже зависит от `BuildOwnerContactHandler`
  (внедрено эпиком B). Фаза A2 здесь ничего не правит — handler сам теперь
  тянет групповые чаты.
- `TelegramNotificationChannel`:
  - `supports()` = `bot_configured && $contact->hasAnyTelegramTarget()`.
  - `deliver()` итерирует `[telegramId, ...telegramChatIds]` (фильтрует null/'').
    Per-target try/catch, ошибки логируются через `LoggerInterface` (PSR-3).
    Если хоть один таргет принял — return; если все упали — бросаем
    `App\Infrastructure\Notifications\Channels\TelegramDeliveryFailed`,
    `MultiChannelOwnerNotifier` уйдёт в fallback (email).
  - В конструктор добавлен `LoggerInterface` (Laravel auto-resolve, биндинги
    в `NotificationsServiceProvider` не трогаем).
- Feature-тест `tests/Feature/Notifications/TelegramChannelMultiTargetTest.php`
  (7 кейсов): доставка в DM+группы, только группы, mixed-success, all-fail
  (бросает `TelegramDeliveryFailed`), `supports()` true/false.
- `tests/Feature/SendNegativeReviewAlertTest.php` обновлён под новый job-сигнатур
  (`BuildOwnerContactHandler` вместо `GetOwnerByIdHandler`) + добавлен
  интеграционный кейс «групповой чат подхватывается из БД и попадает в
  `OwnerContact.telegramChatIds`».

**Принятые мелочи:**

- Без отдельного `Reader` для `ListOwnerTelegramChats`: запросы дешёвые,
  агрегат `OwnerTelegramChat` сам уже плоский — оверкилл городить read-model.
- В `OwnerChatLinkTokenRepository` нет метода `purgeExpired` — TTL короткий
  (10 мин), мусор не критичен; чистка опциональна и решается scheduler-ом
  позже (вне эпика).

---

## Фаза A3 — Telegram bot side (Nutgram) — ✅ (готово 2026-05-29)

- В `App\Interface\TelegramBot\Commands\BotCommandHandler` (или там, где
  обрабатывается `/start <payload>`) добавить ветку «токен принадлежит
  chat_link» → вызов `BindTelegramChatHandler`.
- Хэндлер `my_chat_member` (бот добавлен в группу) — отправляет в группу
  подсказку: «чтобы привязать чат к владельцу, нажмите кнопку в Owner-панели
  с `startgroup=<token>`».
- Конфиг: `nutgram.username` env-параметр, чтобы строить deep-link на бэке.
- Feature-тесты в `tests/Feature/TelegramBot/`:
  - валидный `/start <chat_link_token>` в группе → запись в `owner_telegram_chats`;
  - повторный bind с тем же chat_id — идемпотентно, без дубля;
  - истёкший токен → ошибка пользователю в чате.

### Что сделано

- **Не трогал** `BotCommandHandler`/`OnboardingConversation` (SRP): разнёс
  `/start` на два роута в `routes/telegram.php`:
  - `onCommand('start {token}', ChatLinkCommandHandler::class)` — регистрируется
    **до** bare `start`; Nutgram-regex `^/start (?<token>.*?)$` требует пробел,
    поэтому `/start` без аргумента сюда не попадает.
  - `onCommand('start', OnboardingConversation::class)` — прежний онбординг.
  - `onMyChatMember(BotMembershipHandler::class)` — вне группы
    `RequireRegisteredOwner` (токен/мембершип сами по себе авторизация).
- `App\Interface\TelegramBot\Commands\ChatLinkCommandHandler` (`final readonly`,
  `__invoke(Nutgram, string $token)`):
  - только `group`/`supergroup` (нормализация `ChatType|string`); в личке
    деградирует к `OnboardingConversation::begin($bot)`.
  - вызывает `BindTelegramChatHandler` с `chatId = (string) $chat->id`
    (отрицательный, проходит VO `TelegramChatId`), `title = $chat->title`.
  - ловит `ChatLinkTokenNotFound | ChatLinkTokenExpired |
    ChatLinkTokenAlreadyConsumed` → `TelegramMessages::chatLinkInvalid()`.
    NB: `findActiveByToken` уже отсекает истёкшие/использованные → на практике
    прилетает `ChatLinkTokenNotFound`; доменные исключения — belt-and-suspenders.
  - успех → `TelegramMessages::chatLinked()`.
- `App\Interface\TelegramBot\Commands\BotMembershipHandler` (`__invoke(Nutgram)`):
  через `$bot->chatMember()` (`my_chat_member`) ловит переход
  `left|kicked → member|administrator` в группе → `chatLinkHint()`.
- Тексты — в `TelegramMessages`: `chatLinked()`, `chatLinkInvalid()`,
  `chatLinkHint()`.
- Конфиг deep-link уже есть с фазы A1: `guardreviews.telegram.bot_username`
  (`TELEGRAM_BOT_USERNAME`) — отдельный `nutgram.username` не заводил.
- Тесты `tests/Feature/TelegramBot/ChatLinkTest.php` (5 кейсов): bind в группе,
  идемпотентность (свежий токен + тот же chat_id → 1 строка, title обновлён),
  истёкший токен → ошибка + 0 строк, `/start <token>` в личке → 0 строк,
  `my_chat_member` join → подсказка. Хелперы `groupChat()`,
  `issueChatLinkToken()` локально в файле.
- Прогон: `tests/Feature/TelegramBot tests/Unit` → 313 passed; Pint clean.

---

## Фаза A4 — Owner SPA + feature-gate — ✅ (готово 2026-05-29)

### Backend

Routes (`routes/api.php`, группа `/api/owner` под
`auth:owner + tenant + tenant-owns-session + subscription.active:402 + feature:shared_telegram_chat`):

- `GET /telegram-chats` → список.
- `POST /telegram-chats/issue-link` → `{ deep_link, expires_at }`.
- `DELETE /telegram-chats/{id}`.

FormRequests + контроллеры 5–10 строк (как в `OwnerPlacesController`).

Feature-тесты `tests/Feature/Owner/OwnerTelegramChatsTest.php`:
- happy path (issue + bind через прямой Application call + list);
- 401 без сессии;
- 402 без активной подписки;
- 403 без фичи (фича снята с тарифа);
- 404 при попытке удалить чужой chat-row (см. `ChangeReviewStatusResult::NotOwnedByCaller`).

Добавить case `Feature::SharedTelegramChat = 'shared_telegram_chat'` + строку в
`label()`. Filament `TariffForm` подхватит чекбокс автоматически
(см. `backend/docs/owner-panel.md` §6).

### Frontend (FSD, `frontend/owner/`)

- `entities/telegram-chat/`:
  - `model/types.ts` — `TelegramChat`, `IssueLinkResponse`.
  - `api/useTelegramChatsQuery.ts`.
  - `config/queryKeys.ts`.
  - `index.ts` (public API).
- `features/issue-telegram-chat-link/`:
  - `api/useIssueLinkMutation.ts` (вызывает `ensureCsrf()` + `httpClient.post`).
  - `ui/IssueLinkButton.tsx` — кнопка → копирует deep-link / открывает `tg://`.
- `features/unlink-telegram-chat/`:
  - `api/useUnlinkMutation.ts` с invalidate.
  - `ui/UnlinkChatButton.tsx` через `ConfirmDialog` из `@guard-reviews/shared/ui`.
- `widgets/telegram-chats-card/` — компоновка списка + действий.
- Раздел в `pages/profile/` (или новая `pages/notifications/`) с
  `<FeatureGate feature="shared_telegram_chat" fallback={<UpsellCard/>}>`.
- vitest на mutation и query (моки `@/shared/api`, `sonner`).

### Что сделано

Backend:
- `Feature::SharedTelegramChat = 'shared_telegram_chat'` + label;
  `ApiErrorCode::TelegramChatNotFound = 'telegram_chat_not_found'` + message.
- `OwnerTelegramChatsController` (`index/issueLink/destroy`, 5–10 строк каждый)
  + `OwnerTelegramChatView::fromView()/fromIssuedToken()` (snake_case, ISO 8601).
  В `destroy()` ловится `TelegramChatNotOwnedByCaller` → 404 c
  `telegram_chat_not_found` (не светим чужие строки, как в `OwnerPlacesController`).
  FormRequests не понадобились — у эндпоинтов нет body.
- `routes/api.php`: вложил в существующий блок `auth:owner + tenant +
  tenant-owns-session + subscription.active:402` ещё одну группу
  `feature:shared_telegram_chat` с тремя маршрутами.
- Тесты `tests/Feature/Owner/OwnerTelegramChatsTest.php` (5 кейсов):
  happy path (issue-link → ручной insert строки как имитация работы бота A3
  → list → delete), 401 без сессии, 402 без подписки, 403 без фичи, 404 на
  чужую строку. Все зелёные. Pint clean.

Frontend (`frontend/owner/src/`):
- `entities/telegram-chat/`: `model/types.ts` (`TelegramChat`, `IssuedChatLink`),
  `api/useTelegramChatsQuery.ts` (envelope `{data: T[]}`),
  `config/queryKeys.ts`, `index.ts`.
- `features/issue-telegram-chat-link/`: `useIssueLinkMutation` (с
  `ensureCsrf()` → `httpClient.post`), `IssueLinkButton`
  (`window.open(deep_link, '_blank')` — мобильный Telegram перехватит).
- `features/unlink-telegram-chat/`: `useUnlinkMutation` с invalidate
  `telegramChatsQueryKeys.list()`, `UnlinkChatButton` через `ConfirmDialog`
  + `toast`.
- `widgets/telegram-chats-card/`: список (метка = `title` или `chat_id`,
  дата привязки) + кнопка issue-link + подсказка с показом выданного
  `deep_link` и его сроком.
- `pages/profile/ui/ProfilePage.tsx`: добавлен блок
  `<FeatureGate feature="shared_telegram_chat" fallback={<UpsellCard …/>}>
  <TelegramChatsCard/></FeatureGate>` после `PushSettingsCard`.
- `entities/features/model/types.ts`: добавлен union-кейс `'shared_telegram_chat'`.
- vitest: `useIssueLinkMutation.test.tsx` (моки `@/shared/api`,
  проверяет `ensureCsrf` + POST + конверт), `useUnlinkMutation.test.tsx`
  (DELETE + `invalidateQueries`).
- Зелёное: `npx tsc --noEmit`, `npx vitest run` (23 файла / 61 тест),
  `npx eslint` по новым папкам.

---

## Фаза A5 — Документация + ops — ✅ (готово 2026-05-29)

- Обновить `backend/docs/owner-panel.md`:
  - §4 — добавить 3 новых endpoint'а.
  - §5 — добавить новые use cases.
  - §6 — упомянуть как пример добавленного feature-gate.
- В `docs/TELEGRAM_RU.md` описать UX-процесс привязки чата (скриншоты
  не обязательны, текст шагов — да).
- Проверить, что в `.env.example` есть `NUTGRAM_USERNAME` (или эквивалент)
  для генерации deep-link.

### Что сделано

- **`backend/docs/owner-panel.md`:**
  - §4 — в таблицу «Требует активной подписки» добавлены 3 строки
    (`GET/POST/DELETE /telegram-chats…`) с гардом `feature:shared_telegram_chat`
    (403). Под таблицей пояснён workflow: POST отдаёт
    `{deep_link, expires_at}`, привязка делается ботом по `/start <token>`
    (фаза A3), TTL = `guardreviews.chat_link.ttl_seconds` (600 с по умолчанию).
    В список `ApiErrorCode` добавлен `telegram_chat_not_found`.
  - §5 — в таблицу Owner-relevant use cases добавлены
    `IssueTelegramChatLinkToken`, `BindTelegramChat` (с пометкой «вызывается
    ботом»), `ListOwnerTelegramChats`, `UnlinkTelegramChat`. У
    `BuildOwnerContact` уточнено «подтягивает `telegramChatIds` владельца».
  - §6 — в конце «Добавить feature-gate (паттерн)» добавлен блок «Свежий
    пример end-to-end: `shared_telegram_chat`» со ссылкой на эпик и кейсы
    тестов.
- **`docs/TELEGRAM_RU.md`** — в конец добавлен раздел «Привязка общего
  Telegram-чата (для владельца)»: 5 шагов UX (Профиль → карточка → кнопка →
  Telegram → подтверждение) + блок «Технические детали» (TTL токена,
  идемпотентность, отвязка, fallback-подсказка при ручном добавлении бота,
  семантика «доставлено = хотя бы один таргет принял»). Со ссылкой на план.
- **`backend/.env.example`** — отдельный env-параметр `NUTGRAM_USERNAME`
  не нужен: deep-link уже строится из существующего `TELEGRAM_BOT_USERNAME`
  (`guardreviews.telegram.bot_username`); добавил поясняющий комментарий рядом
  с переменной + добавил `TELEGRAM_CHAT_LINK_TTL_SECONDS=600` (как раз
  ту, что читается в `IssueTelegramChatLinkTokenHandler`).
- `backend/саммари.md` уже обновлён в фазе A1 — use cases и агрегаты
  эпика A перечислены в строке «Iam».

---

## Чек-лист соответствия принципам

- **SOLID/DDD:** новые получатели = расширение `OwnerContact` (immutable VO) +
  изменение цикла внутри существующего канала. Open/closed: ядро
  `MultiChannelOwnerNotifier` не трогается, контракт `NotificationChannel`
  не меняется.
- **DRY:** магик-токены переиспользуют (или клонируют) механику
  `owner_login_requests`; `BuildOwnerContact` — единый сборщик контакта для
  всех job'ов, не дублируем join'ы.
- **KISS:** ни нового context'а, ни новой очереди, ни новой Telegram-обёртки.
  Дельта = 1 миграция + 1 enum-case + цикл в одном канале + 4 use case'а +
  3 endpoint'а + 1 виджет фронта.

---

## Контракт «готово»

1. `composer test` зелёный (unit + feature).
2. `cd frontend/owner && npm run lint && npm run typecheck && npm run test && npm run build` — без ошибок.
3. `./vendor/bin/pint --test` — без диффа.
4. Ручная проверка: создать тариф с фичей → залогиниться в кабинет → нажать
   «Привязать общий чат» → добавить бота в тестовую TG-группу с deep-link →
   оставить негативный отзыв на тенанте → алерт пришёл и в ЛС, и в группу.
5. Обновлены `backend/docs/owner-panel.md` и `docs/TELEGRAM_RU.md`.
