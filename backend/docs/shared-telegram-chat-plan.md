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

## Фаза A2 — Infrastructure

- Eloquent-модель `App\Models\OwnerTelegramChat` (UUID PK, fillable, casts).
- Mapper `App\Infrastructure\Persistence\Eloquent\Iam\OwnerTelegramChatMapper`.
- `EloquentOwnerTelegramChatRepository` + Reader (для Query из A1).
- Биндинги в `IamServiceProvider`.
- Обновить `App\Jobs\SendNegativeReviewAlert::handle()`: вместо
  `$owner->asNotificationContact()` — `BuildOwnerContact->handle(ownerId)`.
- **Расширить `TelegramNotificationChannel.deliver()`:**
  - цикл по targets `[$contact->telegramId, ...$contact->telegramChatIds]`
    (отфильтровать null);
  - per-target try/catch, ошибки агрегируются;
  - `delivered = true`, если хотя бы один target отправлен;
  - если все упали — пробрасываем исключение (как сейчас), `MultiChannel`
    логирует и переходит к fallback.
  - **Не бросать из канала на «нет ни одного target»** — `supports()` уже
    отфильтрует через `hasAnyTelegramTarget()`.
- Feature-тест канала с моком `Nutgram`: 2 targets → 2 вызова `sendMessage`.

---

## Фаза A3 — Telegram bot side (Nutgram)

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

---

## Фаза A4 — Owner SPA + feature-gate

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

---

## Фаза A5 — Документация + ops

- Обновить `backend/docs/owner-panel.md`:
  - §4 — добавить 3 новых endpoint'а.
  - §5 — добавить новые use cases.
  - §6 — упомянуть как пример добавленного feature-gate.
- В `docs/TELEGRAM_RU.md` описать UX-процесс привязки чата (скриншоты
  не обязательны, текст шагов — да).
- Проверить, что в `.env.example` есть `NUTGRAM_USERNAME` (или эквивалент)
  для генерации deep-link.

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
