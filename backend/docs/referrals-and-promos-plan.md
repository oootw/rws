# Реферальная программа и промокоды — план реализации

> Документ-handoff для **холодной сессии**. Содержит всё, что нужно, чтобы войти в проект «с нуля» и начать реализацию по фазам, не задавая лишних вопросов.
>
> Обязательно прочитать перед стартом:
> - `backend/docs/architecture.md` — слои и правила DDD.
> - `backend/саммари.md` — карта контекстов, шаблоны use case'ов.
> - `backend/docs/admin.md` — устройство Filament-админки.
> - `backend/docs/owner-panel.md` — паттерны owner-SPA (новый «marketer-SPA» будет точной копией паттерна).

---

## 1. Цели (что хочет заказчик)

1. **Реферальная программа.** Маркетолог регистрируется в системе, получает уникальный реферальный код/ссылку, приглашает владельцев заведений (`Owner`). За **первый платёж** приглашённого владельца маркетолог получает вознаграждение, размер которого зависит от выбранного периода подписки:
   - месяц → 1 000 ₽,
   - год → 12 000 ₽,
   - суммы и условия регулируются админом в супер-админке (`/admin`).
2. **Личный кабинет маркетолога.** Простая статистика:
   - сколько владельцев привлёк (всего/активные/оплатили),
   - сколько заработал (начислено/выплачено/в ожидании),
   - история выплат.
3. **Промокоды / гибкие бонусы.** Владелец вводит код при оплате и получает:
   - скидку (% или фикс. сумма),
   - либо бонус-фичу из тарифа на N дней,
   - либо дополнительные дни подписки в подарок.
   Условия (срок, лимиты, тип) — управляются в админке.

---

## 2. Архитектурные решения (что фиксируем до старта)

### 2.1. Новые bounded contexts

| Контекст      | Зачем                                                                            |
|---------------|----------------------------------------------------------------------------------|
| `Marketing`   | Маркетолог, реферальный код, атрибуция владельцев, начисления, выплаты.          |
| `Promotions`  | Промокоды, типы скидок/бонусов, погашения.                                       |

Оба самодостаточны и общаются с другими контекстами **только через Application use cases и Domain Events** (см. `architecture.md` § «Bounded Contexts»). Eloquent-модели чужого контекста дёргать нельзя.

### 2.2. Расширение существующих контекстов

#### Iam
- Ввести **`BillingPeriod`** (enum: `Month`, `Year`; backing-value стабилен). От него зависят: длительность продления подписки и комиссия маркетолога.
- Расширить `Tariff`: цены по периоду — `monthlyPrice`, `yearlyPrice` (копейки). Старое поле `basePrice` остаётся как fallback / помечается deprecated в фазе 1 и удаляется в фазе 7.
- Добавить **`OwnerFeatureGrant`** (агрегат `Iam`): owner_id, feature, expires_at. Это позволяет промокодам типа «подарить фичу на N дней» работать без правки тарифа.
- `GetOwnerFeaturesHandler` объединяет `tariff.features` ∪ активные `OwnerFeatureGrant` (источник истины для `RequireFeature` middleware).
- Расширить `RegisterOwnerCommand` опциональным `?string $referralCode` — атрибуция к маркетологу делается в листенере доменного события `OwnerRegistered` (см. § 2.4).

#### Payments
- `PaymentTransaction`: добавить `BillingPeriod $period`, `?int $discountMinorUnits`, `?string $appliedPromoCode`. Поле `amount` уже хранит **итог к оплате** (после скидки); базовый прайс восстанавливается как `amount + discountMinorUnits`.
- `InitSubscriptionPaymentCommand`: добавить `BillingPeriod $period`, `?string $promoCode`.
- `InitSubscriptionPaymentHandler`:
  - выбирает прайс по `period` из `Tariff`;
  - вызывает `Promotions\ApplyPromoCodeToCheckout` (Query) — возвращает `DiscountedQuote{ amountMinorUnits, discountMinorUnits, freeFeatureGrants }`;
  - записывает выбор в транзакцию.
- `HandlePaymentNotificationHandler` после `confirm()`:
  - вызывает `ExtendSubscriptionHandler` с `durationDays` = `period.toDays()` (30 или 365);
  - диспатчит `SubscriptionPaymentConfirmed { ownerId, transactionId, period, amount, appliedPromoCode }`.
- Это событие слушают:
  - `Marketing\Listeners\AccrueCommissionOnFirstPayment` — начисляет комиссию ровно один раз для пары (marketer, owner);
  - `Promotions\Listeners\FinalizePromoRedemption` — фиксирует погашение промокода (счётчик `redeemed_count++`);
  - `Iam\Listeners\GrantPromoFeatures` — если промокод включал бонус-фичи, создаёт `OwnerFeatureGrant`.

### 2.3. Аутентификация маркетолога

Новый guard `marketer` (driver=session, provider=eloquent users-таблица отдельная — `marketers`). Логин: email + password (одно-разовая регистрация через публичную форму). Сессия — Sanctum cookie, как у owner-SPA. **Magic-code через Telegram не используем** — у маркетологов нет привязки к боту, форма «email+password» — самое простое и привычное для B2B-партнёрки.

- Регистрация: открытая страница `/partner/register` — публичный POST `/api/marketer/auth/register` (email уникален, throttle 5/min, CAPTCHA как у `SubmitReview`).
- Логин: `/partner/login` → POST `/api/marketer/auth/login` (throttle 10/min).
- Восстановление пароля: стандартный Laravel password broker, отдельный канал.
- Cookie-сессия как у owner: `EnsureFrontendRequestsAreStateful` + CSRF.

### 2.4. Frontend

Новый SPA `frontend/marketer/` — клон структуры `frontend/owner/`:
- Vite + React 18 + TS + Tailwind + TanStack Query + Zustand, тот же `frontend/shared/` (design-tokens, UI-примитивы, preset).
- FSD: `app/pages/widgets/features/entities/shared`. ESLint-boundaries.
- Сборка в `dist/marketer/`, отдаётся через `MarketerSpaController` (по аналогии с `OwnerSpaController`), nginx — `^~ /partner/` со статикой + fallback в Laravel.
- Доступ — на основном домене `otziv.space/partner/*` (без поддомена-тенанта, т.к. маркетолог не привязан к заведению).

### 2.5. Что НЕ делаем в v1 (out of scope)

- Автоматическая выплата маркетологу (через банк/Тинькофф) — выплаты помечает админ вручную в Filament. Появится позже, когда будут реальные маркетологи.
- Многоуровневая реферальная сеть (MLM) — только одноуровневая.
- Анти-фрод сложный (повторные регистрации с того же IP) — пока только уникальность `email` и `subdomain_slug` владельца + ручная модерация в Filament.
- Реф-ссылки на конкретный тариф / лендинг под маркетолога — только общий код в URL.

---

## 3. Доменная модель

### 3.1. `Marketing`

**Aggregates / VOs:**

```
Domain/Marketing/
├── Marketer.php                  (aggregate root)
├── MarketerId.php                (VO, UUID)
├── MarketerIdGenerator.php       (port)
├── MarketerRepository.php        (port)
├── ReferralCode.php              (VO: 6–10 символов, base32 без 0/O/I/1)
├── Referral.php                  (aggregate: marketer_id + owner_id + attributed_at + first_payment_id?)
├── ReferralId.php                (VO)
├── ReferralIdGenerator.php       (port)
├── ReferralRepository.php        (port)
├── Commission.php                (aggregate: marketer + referral + payment + amount + status)
├── CommissionId.php
├── CommissionIdGenerator.php
├── CommissionStatus.php          (enum: Pending, Approved, Paid, Voided)
├── CommissionRepository.php
├── CommissionRule.php            (read-сущность: period → amount)
├── CommissionRuleId.php
├── CommissionRuleRepository.php  (findByPeriod, listAll)
└── Events/
    ├── MarketerRegistered.php
    ├── OwnerAttributedToMarketer.php
    └── CommissionAccrued.php
```

**Инварианты:**
- `ReferralCode` уникален в `marketers` (БД-индекс + проверка в репозитории).
- На пару `(marketer_id, owner_id)` существует **один** `Referral` (БД-уникальный индекс).
- На пару `(marketer_id, owner_id)` существует **максимум одна** `Commission` со статусом ≠ `Voided` (БД-уникальный частичный индекс).
- `Commission::approve()` возможен только из `Pending`; `pay()` — только из `Approved`.

### 3.2. `Promotions`

```
Domain/Promotions/
├── PromoCode.php                 (aggregate root)
├── PromoCodeId.php
├── PromoCodeIdGenerator.php
├── PromoCodeRepository.php
├── PromoCodeRule.php             (VO: тип эффекта + параметры)
├── PromoCodeRuleKind.php         (enum: PercentDiscount, FixedDiscount, ExtendDays, GrantFeature)
├── PromoCodeStatus.php           (enum: Active, Disabled, Expired)
├── PromoRedemption.php           (aggregate)
├── PromoRedemptionId.php
├── PromoRedemptionIdGenerator.php
├── PromoRedemptionRepository.php
└── Events/
    └── PromoCodeRedeemed.php
```

**Инварианты:**
- `PromoCode.code` уникален (uppercase + нормализация в VO).
- `redeemedCount <= maxRedemptions` (если задан лимит). Проверка в `PromoCode::redeem()` + БД-counter с pessimistic lock в момент confirm (см. § 6 «Промокоды и гонки»).
- Один `(promo_code_id, owner_id)` — один redemption (если в правиле стоит `one_per_owner = true`).
- Валидность по периоду: `validFrom <= now <= validUntil`.
- `PercentDiscount.value ∈ [1, 100]`; `FixedDiscount.value > 0`; `ExtendDays.value > 0`; `GrantFeature` — список фич + длительность в днях.

### 3.3. Изменения в `Iam`

```
Domain/Iam/
├── BillingPeriod.php             (NEW enum: Month=30 days, Year=365 days; toDays(), label())
├── Tariff.php                    (NEW: monthlyPrice, yearlyPrice, deprecated basePrice)
├── OwnerFeatureGrant.php         (NEW aggregate)
├── OwnerFeatureGrantId.php
├── OwnerFeatureGrantIdGenerator.php
├── OwnerFeatureGrantRepository.php
└── Events/
    └── OwnerRegistered.php       (NEW — для атрибуции к маркетологу)
```

`Owner.register(...)` уже существует — добавить параметр `?ReferralCode $referralCode` опционально, или (предпочтительно) **не трогать агрегат**, а атрибуцию делать в листенере `OwnerRegistered` события (тогда `Iam` не знает о `Marketing` — это правильно, направление зависимостей соблюдено).

### 3.4. Изменения в `Payments`

```
Domain/Payments/
├── PaymentTransaction.php        (NEW поля: period, discountMinorUnits, appliedPromoCode)
└── Events/
    └── SubscriptionPaymentConfirmed.php  (NEW — слушают Marketing, Promotions, Iam)
```

---

## 4. Application use cases

### 4.1. `Marketing` (new)

| Use case                              | Тип     | Назначение                                                              |
|---------------------------------------|---------|-------------------------------------------------------------------------|
| `RegisterMarketer`                    | Command | Регистрация: email+password+name → создаёт `Marketer` + `ReferralCode`. |
| `AuthenticateMarketer`                | Command | Login (через Sanctum, тонко в контроллере).                             |
| `GetMarketerById`                     | Query   | DTO для `/me`.                                                          |
| `GetMarketerDashboard`                | Query   | KPI: всего приглашено / оплатили / комиссия начислено/выплачено/ждёт.   |
| `ListMarketerReferrals`               | Query   | Reader: paginated список приглашённых, со статусом первой оплаты.       |
| `ListMarketerCommissions`             | Query   | Reader: paginated история начислений + выплат.                          |
| `AttributeOwnerToMarketer`            | Command | Вызывается листенером `OwnerRegistered`; идемпотентно по `owner_id`.    |
| `AccrueCommission`                    | Command | Вызывается листенером `SubscriptionPaymentConfirmed`. Идемпотентно.     |
| `ApproveCommission`                   | Command | Admin-action в Filament (`Pending → Approved`).                         |
| `MarkCommissionPaid`                  | Command | Admin-action (`Approved → Paid`, добавляет `paid_at`).                  |
| `VoidCommission`                      | Command | Admin-action (например, при возврате платежа).                          |
| `UpsertCommissionRule`                | Command | Admin: задать сумму комиссии для `BillingPeriod`.                       |
| `ListCommissionRules`                 | Query   | Для Filament-таблицы и для `AccrueCommission`.                          |

### 4.2. `Promotions` (new)

| Use case                              | Тип     | Назначение                                                              |
|---------------------------------------|---------|-------------------------------------------------------------------------|
| `ApplyPromoCodeToCheckout`            | Query   | Принимает `{code, ownerId, tariffId, period}`. Возвращает `DiscountedQuote{amountMinorUnits, discountMinorUnits, grants[], reason?}`. Не создаёт redemption — только расчёт. |
| `ReservePromoRedemption`              | Command | На init-payment: создаёт `PromoRedemption` со статусом `Reserved` (опц., см. § 6). |
| `FinalizePromoRedemption`             | Command | Листенер `SubscriptionPaymentConfirmed`. Меняет `Reserved → Redeemed`, инкрементирует `redeemed_count`. |
| `CreatePromoCode`/`UpdatePromoCode`   | Command | Filament — мутации через handler ради инвариантов нормализации кода и валидации полей. |
| `DisablePromoCode`                    | Command | Filament action (мягкое выключение).                                    |
| `GetPromoCodeByCode`                  | Query   | Используется `ApplyPromoCodeToCheckout`.                                |

### 4.3. Изменения существующих

| Use case                              | Что меняется                                                            |
|---------------------------------------|-------------------------------------------------------------------------|
| `RegisterOwnerCommand`                | + `?string $referralCode`. Handler **только пишет** этот код в событие `OwnerRegistered` (если код валидный — резолвится `MarketerRepository::findByCode`, иначе игнор). Атрибуцию делает листенер. |
| `InitSubscriptionPaymentCommand`      | + `BillingPeriod $period`, `?string $promoCode`.                        |
| `InitSubscriptionPaymentHandler`      | Считает прайс из тарифа по периоду, прогоняет `ApplyPromoCodeToCheckout`, сохраняет `period` + `discount` + `appliedPromoCode` в транзакции. |
| `HandlePaymentNotificationHandler`    | Передаёт `period.toDays()` в `ExtendSubscriptionHandler`. Диспатчит `SubscriptionPaymentConfirmed`. |
| `GetOwnerFeaturesHandler`             | Union `tariff.features` ∪ активные `OwnerFeatureGrant` (по `expires_at > now`). |
| `CalculateSubscriptionAmountHandler`  | Принимает `BillingPeriod` (через query). Цены берутся из `Tariff::priceFor(period)`. |

---

## 5. Infrastructure / БД

### 5.1. Новые таблицы

```sql
-- Marketing
marketers (
  id uuid PK, name text, email citext UNIQUE, password_hash text,
  referral_code citext UNIQUE,            -- из VO ReferralCode
  status text DEFAULT 'active',           -- active|disabled
  created_at, updated_at
)

referrals (
  id uuid PK,
  marketer_id uuid FK marketers,
  owner_id uuid FK users UNIQUE,          -- UNIQUE → owner может быть прикреплён к одному маркетологу навсегда
  attributed_at timestamptz,
  first_paid_transaction_id uuid NULL FK payment_transactions,
  created_at, updated_at,
  UNIQUE(marketer_id, owner_id)
)

commissions (
  id uuid PK,
  marketer_id uuid FK,
  referral_id uuid FK,
  payment_transaction_id uuid FK UNIQUE,  -- одна транзакция = одна комиссия
  amount_minor_units bigint,
  status text,                            -- pending|approved|paid|voided
  approved_at timestamptz NULL,
  paid_at timestamptz NULL,
  voided_at timestamptz NULL,
  created_at, updated_at
)

commission_rules (
  id uuid PK,
  period text UNIQUE,                     -- month|year (для будущих периодов — расширяемо)
  amount_minor_units bigint,
  is_active boolean DEFAULT true,
  created_at, updated_at
)

-- Promotions
promo_codes (
  id uuid PK,
  code citext UNIQUE,                     -- нормализованный uppercase
  kind text,                              -- percent|fixed|extend_days|grant_feature
  payload jsonb,                          -- {value: 10} | {value: 1000} | {days: 7} | {features: ['weekly_digest'], days: 30}
  status text,                            -- active|disabled
  valid_from timestamptz NULL,
  valid_until timestamptz NULL,
  max_redemptions int NULL,
  redeemed_count int DEFAULT 0,
  one_per_owner boolean DEFAULT true,
  applies_to_period text NULL,            -- NULL|month|year — ограничение применимости
  applies_to_tariff_id uuid NULL,
  min_amount_minor_units bigint NULL,
  created_at, updated_at
)

promo_redemptions (
  id uuid PK,
  promo_code_id uuid FK,
  owner_id uuid FK users,
  payment_transaction_id uuid FK NULL,
  applied_discount_minor_units bigint NULL,
  applied_payload jsonb,                  -- снимок payload на момент применения
  status text,                            -- reserved|redeemed|cancelled
  reserved_at timestamptz,
  redeemed_at timestamptz NULL,
  cancelled_at timestamptz NULL,
  UNIQUE(promo_code_id, owner_id)         -- если one_per_owner=true; иначе ограничение снимается на уровне use case
)

-- Iam
owner_feature_grants (
  id uuid PK,
  owner_id uuid FK,
  feature text,                           -- backing-value из Feature enum
  expires_at timestamptz NULL,            -- NULL = бессрочно
  source text,                            -- 'promo'|'admin'|'system'
  source_ref uuid NULL,                   -- promo_redemption_id и т.п.
  created_at
)
CREATE INDEX ON owner_feature_grants (owner_id) WHERE expires_at IS NULL OR expires_at > now();

-- Payments — alter
ALTER TABLE payment_transactions
  ADD COLUMN period text NOT NULL DEFAULT 'month',     -- backfill: month
  ADD COLUMN discount_minor_units bigint NULL,
  ADD COLUMN applied_promo_code text NULL;

-- Tariffs — alter
ALTER TABLE tariffs
  ADD COLUMN monthly_price bigint NULL,
  ADD COLUMN yearly_price bigint NULL;
-- backfill: monthly_price = base_price; yearly_price = base_price * 10 (или вручную)
```

### 5.2. Bindings

- Новый `MarketingServiceProvider` (репозитории, генераторы ID, листенеры доменных событий).
- Новый `PromotionsServiceProvider`.
- `IamServiceProvider` дополнить биндингом `OwnerFeatureGrantRepository` + регистрацией листенера `OwnerRegistered → AttributeOwnerToMarketer`.
- `PaymentsServiceProvider` дополнить регистрацией листенеров `SubscriptionPaymentConfirmed → Accrue / Finalize / Grant`.
- Все провайдеры добавить в `bootstrap/providers.php`.

---

## 6. Тонкости и подводные камни

### 6.1. Идемпотентность комиссий

`AccrueCommissionHandler` ловит событие `SubscriptionPaymentConfirmed`. Может прилететь дважды (webhook ретраи Тинькофф) — даже несмотря на `transaction->isFinalized()` ранний return в `HandlePaymentNotificationHandler`. Защита:
- БД-индекс `commissions.payment_transaction_id UNIQUE` → второй insert падает.
- Handler ловит constraint violation → no-op (логировать на info).

### 6.2. «Первый платёж»

«Первый» — это первый **successful** платёж этого `Owner`. Реализация:
- В `AccrueCommissionHandler` идём в `PaymentTransactionRepository::successCountByOwner($ownerId)`. Если `> 1` — выходим.
- Альтернатива (более чистая): `Referral.first_paid_transaction_id` — если NULL → это первый, начисляем + проставляем; если уже стоит — пропускаем. Лучше эта, потому что не нужен cross-context counter.

### 6.3. Промокоды и гонки

`max_redemptions` — глобальный счётчик. Чтобы избежать «N+1 пользователей прошли проверку на этапе init-payment»:
- На `init-payment` создаём `PromoRedemption` со статусом `Reserved` (резерв слота). TTL: 30 мин (cron подметает истёкшие резервы).
- В `FinalizePromoRedemptionHandler` обновляем `Reserved → Redeemed`. Инкремент `redeemed_count` происходит **в транзакции** с проверкой `redeemed_count < max_redemptions`.
- Если init-payment превышает лимит — отдаём ошибку владельцу до похода в Тинькофф.

### 6.4. Откаты подписки

Если платёж отменён/возвращён (статус `Refunded`/`Failed` после ранее `Success` — пока не реализовано, но архитектурно возможно через `VoidCommission`):
- `VoidCommissionHandler` ставит `voided` (если ещё `Pending`/`Approved`) — нельзя ставить если `Paid`.
- `CancelPromoRedemptionHandler` ставит `cancelled` и декрементирует счётчик.
- Пока в v1 ручное действие админа.

### 6.5. Реф-ссылка и атрибуция

Маркетолог делится ссылкой вида `https://otziv.space/ref/ABC123` (или `?ref=ABC123` на любом лендинге). Cookie-attribution:
- Middleware `CaptureReferralCookie` на корне сайта — если `?ref=XXX`, ставит cookie `gr_ref=XXX` на 90 дней.
- В Telegram-онбординге (`OnboardingConversation`) код **из cookie не виден** → используем deep-link: `https://t.me/<bot>?start=ref_ABC123`. `BotCommandHandler` уже умеет принимать start-payload, добавляем кейс `ref_*` → сохраняем код в conversation-state → передаём в `RegisterOwnerCommand`.
- Для будущей веб-регистрации owner — берём из cookie.

### 6.6. Безопасность маркетолога

- Пароли — bcrypt через стандартный `Hash::make` (как у Laravel `User`).
- Rate-limit: register 5/min, login 10/min, password reset 3/15min.
- Все мутации в кабинете маркетолога — под Sanctum cookie + CSRF (`ensureCsrf()`).
- 404 вместо 403 на чужие ресурсы (как в owner-SPA — см. `owner-panel.md` § 8).

### 6.7. UI/UX мелочи

- При вводе промокода на чекауте — отдельный AJAX-запрос `POST /api/owner/checkout/quote` (НЕ init-payment), возвращает `DiscountedQuote`. Пользователь видит цену до похода в банк.
- На странице owner показывать «вы приглашены маркетологом» (если есть `Referral`) — мелочь, но повышает доверие. Опционально.

### 6.8. Production-safety в Filament

- `MarkCommissionPaid` — без `->visible(!isProduction())` (это бизнес-действие).
- `CreatePromoCode` — обычный CRUD.
- Любое действие с массовым побочным эффектом (например, «начислить всем маркетологам выплату за месяц») — под `->visible(!isProduction())` до тех пор, пока не появится подтверждение.

---

## 7. Поэтапный план реализации

Каждая фаза самодостаточна и заканчивается: `composer test` зелёный, `pint --test` чистый, `frontend` lint/typecheck/test/build зелёные.

### Фаза 0 — Подготовка и расширение `Tariff` + `BillingPeriod`
**Цель:** без новых фич — переключить систему на «период оплаты как параметр».

- [ ] `Domain/Iam/BillingPeriod.php` (enum + `toDays()`, `label()`).
- [ ] Миграция `tariffs.monthly_price`, `tariffs.yearly_price`. Seeder + backfill (по умолчанию `yearly_price = monthly_price * 10`).
- [ ] `Tariff::priceFor(BillingPeriod): int` (fallback на `basePrice` пока поля NULL).
- [ ] `Tariff` mapper, `TariffForm` (Filament) — добавить два поля.
- [ ] `payment_transactions.period` (default `'month'` для backfill), `discount_minor_units`, `applied_promo_code`. `PaymentTransaction` агрегат и маппер.
- [ ] `CalculateSubscriptionAmountHandler` — принимает `BillingPeriod` в Query.
- [ ] `InitSubscriptionPaymentCommand` + handler — принимает `BillingPeriod` (frontend пока всегда шлёт `Month`).
- [ ] `HandlePaymentNotificationHandler` — продлевает на `period.toDays()`. Диспатчит **новое событие** `SubscriptionPaymentConfirmed` (пока без слушателей).
- [ ] Owner-SPA: на `/subscription` — селект «месяц / год» с пересчётом цены через `GET /api/owner/subscription?period=...`.
- [ ] Тесты: unit (`Tariff::priceFor`), unit (handler с разными периодами), feature (init-payment с `period`), регрессии существующих owner-тестов.
- [ ] Обновить `backend/docs/owner-panel.md` § 4 (новые поля).

**Выход:** существующая функциональность работает как раньше + впервые появляется «годовая подписка».

---

### Фаза 1 — Контекст `Marketing`: домен + БД + регистрация маркетолога
**Цель:** маркетолог может зарегистрироваться, получить код, увидеть пустой кабинет.

- [ ] Полный `Domain/Marketing/` (см. § 3.1) — агрегаты, VO, репозитории-интерфейсы. Unit-тесты.
- [ ] Миграции: `marketers`, `referrals`, `commissions`, `commission_rules`.
- [ ] `Infrastructure/Persistence/Eloquent/Marketing/*` — модели, мапперы, репозитории.
- [ ] `MarketingServiceProvider` + регистрация в `bootstrap/providers.php`.
- [ ] `config/auth.php` — guard `marketer` (driver=session, provider=marketers).
- [ ] `config/sanctum.php` — добавить guard в `stateful`.
- [ ] Application use cases: `RegisterMarketer`, `GetMarketerById`, `GetMarketerDashboard` (заглушка с нулями), `ListMarketerReferrals` (пусто), `ListMarketerCommissions` (пусто).
- [ ] Контроллеры `Interface/Http/Controllers/Marketer/Auth/{RegisterMarketerController, LoginMarketerController, LogoutMarketerController}`, `MarketerMeController`, `MarketerDashboardController`. FormRequest'ы.
- [ ] Routes: группа `/api/marketer/*` (зеркало `/api/owner/*` без tenant-middleware — это **глобальный** партнёрский домен).
- [ ] Feature-тесты: register, login, logout, /me, 401 без сессии, throttle.

**Выход:** API маркетолога живой, без UI.

---

### Фаза 2 — SPA маркетолога: scaffold + страницы register/login/dashboard
**Цель:** живой кабинет на `otziv.space/partner` с пустым дашбордом и реф-кодом.

- [ ] `frontend/marketer/` — клон структуры `frontend/owner/`. Vite + React + Tailwind preset. Workspace в `frontend/package.json`. Скрипты `dev:marketer`, `build:marketer`, `test --workspace marketer`.
- [ ] `MarketerSpaController` (`Interface/Http/Controllers/Marketer/MarketerSpaController.php`) — отдаёт `dist/marketer/index.html`. Route `Route::get('/partner/{any?}', ...)` в `routes/web.php`.
- [ ] Docker: `php`-image и `nginx`-image — `COPY --from=frontend /dist/marketer/`. Nginx-location `^~ /partner/`.
- [ ] FSD-структура: `entities/session`, `features/auth-marketer`, `pages/login`, `pages/register`, `pages/dashboard`, `widgets/app-shell`, `widgets/referral-code-card`.
- [ ] Дашборд: блоки «Ваш код / ваша ссылка», KPI-карточки (приглашено / оплатили / комиссия — нули в v1).
- [ ] Тесты: vitest на mutation hooks (register/login), смок-тесты страниц.

**Выход:** маркетолог может зарегистрироваться, залогиниться и увидеть свой реферальный код. Реф-программа ещё не работает.

---

### Фаза 3 — Атрибуция: реф-код → `Referral`
**Цель:** Owner, пришедший по коду, попадает в `referrals`.

- [ ] `Iam/Events/OwnerRegistered.php` — событие после `Owner::register`.
- [ ] `RegisterOwnerCommand` + handler — принимает `?string $referralCode`; кладёт в событие.
- [ ] `Marketing/AttributeOwnerToMarketer/{Command,Handler}` — листенер `OwnerRegistered`. Идемпотентен. Регистрация в `MarketingServiceProvider::boot()`.
- [ ] Middleware `CaptureReferralCookie` (cookie `gr_ref`, 90 дней). Регистрация на корне `routes/web.php`.
- [ ] `BotCommandHandler` (Telegram) — обработка `/start ref_XXX` → проброс в `OnboardingConversation` → `RegisterOwnerCommand.referralCode`.
- [ ] `ListMarketerReferrals` — Reader (`Infrastructure/Persistence/Eloquent/Marketing/EloquentMarketerReferralsReader`). Поля: owner.email, attributed_at, first_paid_transaction_id (null), period (null).
- [ ] SPA маркетолога: страница `pages/referrals` — таблица с infinity scroll/pagination.
- [ ] Тесты: unit (handler), feature (Telegram start с реф-кодом → referral создан; повтор → не дублируется).

**Выход:** маркетолог видит, кого пригласил. Денег ещё нет.

---

### Фаза 4 — Комиссии: правила, начисление, отображение
**Цель:** при первой успешной оплате приглашённого owner — начисляется комиссия.

- [ ] `Payments/Events/SubscriptionPaymentConfirmed.php` (если ещё не в фазе 0).
- [ ] `Marketing/AccrueCommission/{Command,Handler}` — листенер. Получает `CommissionRule` по `period`. Идемпотентен через `Referral.first_paid_transaction_id` + БД-UNIQUE на `commissions.payment_transaction_id`.
- [ ] `Marketing/UpsertCommissionRule/{Command,Handler}`, `ListCommissionRules`.
- [ ] Filament: `Marketing/CommissionRuleResource` (Eloquent CRUD, по аналогии с `TariffResource`). Сидинг дефолтных правил: month=1000₽, year=12000₽.
- [ ] Filament: `Marketing/MarketerResource` (read-only список + детальная страница с балансом).
- [ ] Filament: `Marketing/ReferralResource` (read-only).
- [ ] Filament: `Marketing/CommissionResource` — read-only список, actions: `Approve` (`Pending→Approved`), `MarkPaid` (`Approved→Paid`, modal с reference), `Void`.
- [ ] `GetMarketerDashboard` — реальные числа.
- [ ] SPA: дашборд + страница `pages/earnings` (история начислений и выплат).
- [ ] Тесты: unit `AccrueCommissionHandler` (happy / повтор → no-op / нет правила → no-op + alert / нет referral → no-op). Feature: webhook Tinkoff → комиссия создана.

**Выход:** базовая реферальная программа работает end-to-end.

---

### Фаза 5 — Контекст `Promotions`: домен + админка
**Цель:** админ может завести промокод, владелец может его ввести и получить скидку.

- [ ] Полный `Domain/Promotions/`. Unit-тесты на инварианты (валидация payload по kind).
- [ ] Миграции: `promo_codes`, `promo_redemptions`.
- [ ] `Infrastructure/Persistence/Eloquent/Promotions/*` + биндинги в `PromotionsServiceProvider`.
- [ ] Use cases: `CreatePromoCode`, `UpdatePromoCode`, `DisablePromoCode`, `GetPromoCodeByCode`, `ApplyPromoCodeToCheckout` (Query → `DiscountedQuote`).
- [ ] Filament: `Promotions/PromoCodeResource` (CRUD через use cases ради нормализации/валидации payload), `PromoRedemptionResource` (read-only).
- [ ] `InitSubscriptionPaymentHandler` — принимает `?string $promoCode`, прогоняет через `ApplyPromoCodeToCheckout`, пишет в транзакцию.
- [ ] Новый endpoint `POST /api/owner/subscription/quote` (без похода в банк) — возвращает `{amount, discount, finalAmount, grants[], reason?}`.
- [ ] Owner-SPA: на `/subscription` — поле «промокод» + кнопка «применить» → перерисовка цены.
- [ ] Тесты: unit `ApplyPromoCodeToCheckout` (все 4 kind'а + лимиты + период), feature на init-payment с кодом / без / с просроченным / с превышением лимита.

**Выход:** скидочные промокоды работают.

---

### Фаза 6 — Бонус-фичи: `OwnerFeatureGrant` + промокоды типа `GrantFeature`
**Цель:** промокод может подарить фичу на N дней.

- [ ] `Domain/Iam/OwnerFeatureGrant.php` + репозиторий. Миграция `owner_feature_grants`.
- [ ] `GetOwnerFeaturesHandler` — union `tariff.features ∪ active grants`.
- [ ] `Iam/GrantOwnerFeature/{Command,Handler}` — общий use case (используется и промо-листенером, и админом).
- [ ] `Promotions/Listeners/GrantPromoFeatures` на `SubscriptionPaymentConfirmed` — если в `promo_redemption.applied_payload` есть `features` — вызывает `GrantOwnerFeature`.
- [ ] `Promotions/Listeners/FinalizePromoRedemption` — `Reserved → Redeemed` + инкремент счётчика в транзакции.
- [ ] Filament: `Iam/OwnerFeatureGrantResource` (read-only список, опц. action `Revoke`).
- [ ] Owner-SPA: на `/profile` — индикатор «у вас активны бонусные фичи» (опционально).
- [ ] Тесты: unit (union фич), feature (промокод-фича → owner получил фичу → middleware пропускает → срок истёк → middleware блокирует).

**Выход:** бонус-фичи работают; админ может через промокод раздать любую фичу из `Feature` enum.

---

### Фаза 7 — Polish, документация, чистка
- [ ] Удалить deprecated `Tariff.basePrice` (или оставить как алиас `monthlyPrice` — решить по факту, если фронт чистый).
- [ ] Дописать `backend/docs/owner-panel.md` § 4 (новые поля на subscription / quote endpoint).
- [ ] Создать `backend/docs/marketer-panel.md` (зеркало owner-panel.md).
- [ ] Создать `backend/docs/promotions.md` (как админу заводить промокоды, типы, примеры).
- [ ] Обновить `backend/саммари.md` § 2 — добавить контексты `Marketing`, `Promotions`.
- [ ] Обновить `docs/TELEGRAM_RU.md` (если есть текст про start-payload — добавить `ref_*`).
- [ ] Smoke-тест на staging: создать маркетолога, пройти полный путь Owner-with-ref → платёж → комиссия.

---

## 8. Чек-лист «всё ли по архитектуре»

Перед merge каждой фазы пройтись:

- [ ] Нет `use Illuminate\…` в `Domain/`.
- [ ] Нет `now()`, `config()`, фасадов в `Domain/Application/` (только через `Clock`, инжект `Config`).
- [ ] Все VO, Command, Query, Handler — `final readonly`. Агрегаты — `final` (но не readonly).
- [ ] Каждый Handler — один публичный метод `handle()`.
- [ ] Контроллеры — 5–10 строк, всё в use case.
- [ ] Eloquent-модели чужого контекста не дёргаются (например, `Marketing` не лезет в `users` через `User::find`).
- [ ] Транзакции — через `TransactionRunner`.
- [ ] События — через `DomainEventDispatcher`.
- [ ] Все webhook-обработчики и комиссионные начисления — идемпотентны (БД-UNIQUE + ранний return).
- [ ] Filament Resource: чтение — Eloquent, запись — через use case (исключение: чистые конфиги типа `CommissionRule`).
- [ ] Сообщения об ошибках — на русском, через `ApiErrorCode` (новые коды добавить в enum).
- [ ] FSD на фронте: импорты только вниз; кросс-слайс — через public API.
- [ ] Server state — только TanStack Query; никаких `axios.get` в `ui/`.

---

## 9. Открытые вопросы (решить до старта фазы 4)

1. **Reward currency** — фиксируем рубли (`amount_minor_units = копейки`). ОК?
2. **Минимальный порог выплаты** — есть ли «выплачиваем только когда накопилось ≥ X»? **Предлагаю: нет порога в v1, выплаты ручные.**
3. **Налоги / самозанятость маркетолога** — нужны ли поля ИНН/договор? **Предлагаю: нет в v1; добавить «реквизиты для выплаты» свободным текстом.**
4. **Каскад при удалении владельца** — `DeleteOwner` сейчас существует. Что делать с `Referral` и `Commission`? **Предлагаю: оставить запись (soft-link), комиссии не трогать.**
5. **Возврат платежа** — пока нет use case, но архитектурно — `Refund → VoidCommission → CancelPromoRedemption`. Откладываем до отдельной задачи.
6. **Промокод + реферал** — могут ли быть одновременно? **Предлагаю: да; они независимы.**
7. **Multi-tariff на сайте** — если в будущем у Owner будет выбор тарифа на чекауте, добавить `tariffId` в `InitSubscriptionPaymentCommand`. Пока — текущий тариф owner-а.

---

## 10. Минимальный набор файлов, которые точно появятся

(быстрый ориентир для AI-агента, чтобы не потеряться)

```
backend/
  database/migrations/
    2026_XX_XX_create_marketers_table.php
    2026_XX_XX_create_referrals_table.php
    2026_XX_XX_create_commissions_table.php
    2026_XX_XX_create_commission_rules_table.php
    2026_XX_XX_create_promo_codes_table.php
    2026_XX_XX_create_promo_redemptions_table.php
    2026_XX_XX_create_owner_feature_grants_table.php
    2026_XX_XX_add_billing_period_to_payment_transactions.php
    2026_XX_XX_add_period_prices_to_tariffs.php
  app/
    Domain/
      Iam/{BillingPeriod, OwnerFeatureGrant*, Events/OwnerRegistered}.php
      Payments/Events/SubscriptionPaymentConfirmed.php
      Marketing/<всё из § 3.1>
      Promotions/<всё из § 3.2>
    Application/
      Iam/GrantOwnerFeature/
      Marketing/{RegisterMarketer,AuthenticateMarketer,GetMarketerDashboard,
                  ListMarketerReferrals,ListMarketerCommissions,
                  AttributeOwnerToMarketer,AccrueCommission,
                  ApproveCommission,MarkCommissionPaid,VoidCommission,
                  UpsertCommissionRule,ListCommissionRules}/
      Promotions/{CreatePromoCode,UpdatePromoCode,DisablePromoCode,
                  GetPromoCodeByCode,ApplyPromoCodeToCheckout,
                  ReservePromoRedemption,FinalizePromoRedemption}/
    Infrastructure/
      Persistence/Eloquent/Marketing/<модели + мапперы + репозитории + ридеры>
      Persistence/Eloquent/Promotions/<то же>
      Persistence/Eloquent/Iam/EloquentOwnerFeatureGrantRepository.php
      ServiceProviders/{MarketingServiceProvider, PromotionsServiceProvider}.php
    Interface/
      Http/Controllers/Marketer/
        Auth/{RegisterMarketerController, LoginMarketerController, LogoutMarketerController}.php
        MarketerSpaController.php
        MarketerMeController.php
        MarketerDashboardController.php
        MarketerReferralsController.php
        MarketerCommissionsController.php
      Http/Requests/Marketer/<FormRequest'ы>
      Http/Middleware/CaptureReferralCookie.php
    Filament/Resources/
      Marketing/{MarketerResource, ReferralResource, CommissionResource, CommissionRuleResource}/
      Promotions/{PromoCodeResource, PromoRedemptionResource}/
      Iam/OwnerFeatureGrantResource/
  config/
    auth.php           # + guard 'marketer'
    sanctum.php        # + 'marketer' в stateful
  routes/
    api.php            # + группа /api/marketer/*
    web.php            # + /partner/{any?} + middleware CaptureReferralCookie

frontend/
  marketer/             # клон frontend/owner/
    src/{app,pages,widgets,features,entities,shared}/
    vite.config.ts, tailwind.config.ts, package.json
  package.json          # + workspace 'marketer' + скрипты dev:marketer/build:marketer

docker/
  php/Dockerfile        # + COPY --from=...frontend dist/marketer
  nginx/Dockerfile      # + COPY --from=...frontend dist/marketer
  nginx/conf            # + location ^~ /partner/
```

---

## 11. История изменений

| Дата       | Автор   | Изменение |
|------------|---------|-----------|
| 2026-05-29 | Claude  | Первая версия плана. Готов к старту фазы 0. |
