# Owner-панель — гайд для команды

Финальный документ по архитектуре, эксплуатации и расширению Owner SPA.
Пишите код, ориентируясь на этот файл (а не на исторические `owner-panel-plan.md`
и `админка-план.md` — там handoff-логи между сессиями).

Связанные документы:
- `backend/саммари.md` — общая DDD-карта проекта.
- `backend/docs/architecture.md` — слои и правила.
- `backend/docs/owner-panel-plan.md` — история по фазам 0–8 (можно
  не читать, если вам нужно «как сейчас», а не «как пришли»).

---

## 1. Что это

Личный кабинет владельца заведения, доступный на его поддомене:
`{slug}.otziv.space/owner`. PWA на React 18 + TypeScript + Tailwind +
TanStack Query + Zustand + Vite. Аутентификация — Sanctum SPA cookie через
magic-code из Telegram-бота.

Backend для панели — отдельная группа `/api/owner/*` под middleware
`tenant + tenant-owns-session`. Мутации требуют активной подписки (402).

Полная карта endpoint'ов: см. § 4.

---

## 2. Архитектурный контракт

### Frontend (строго)

- **FSD:** слои `app → pages → widgets → features → entities → shared`,
  импорты только вниз, кросс-слайс — через public API (`<slice>/index.ts`).
  Enforced `eslint-plugin-boundaries`.
- **FP-only:** ES6-классы запрещены ESLint'ом. Состояние — hooks + Zustand,
  pure-функции — в `<slice>/lib/`. Для кастомных Error — паттерн
  `Error + name-тег + type guard` (см. `isProfileValidationError`).
- **Server state — только TanStack Query.** Прямой `fetch`/`axios.get` в
  компонентах/страницах запрещён. HTTP-вызовы — только через React Query
  hooks в `<slice>/api/`.
- HTTP-клиент один: `shared/api/httpClient.ts` (axios + `withCredentials`,
  `ensureCsrf()` перед мутациями, глобальный 402-interceptor).

### Backend (тонкий слой)

Filament-like: контроллер 5–10 строк, всё реальное — в Application use case.

- **Чтение** — через Reader-порты (`OwnerReviewsReader`, `OwnerPaymentsReader`,
  `WeeklySummaryReader`) либо через Query-handler'ы (`GetOwnerSubscription`,
  `GetPlaceForOwner`).
- **Запись** — через Command-handler'ы (`UpdateOwnerProfile`,
  `ChangeReviewStatus`, `InitSubscriptionPayment`, `RegisterPlace`, ...).
- Eloquent-модели чужого контекста дёргать нельзя; всё через use cases.

---

## 3. Структура файлов

### Frontend (`frontend/owner/`)

```
src/
├── app/                       # composition root
│   ├── providers/             # QueryClientProvider, ToasterProvider
│   ├── router/                # AppRouter + RequireSession (cookie-сессия)
│   ├── styles/
│   └── index.tsx
├── pages/                     # 1 route = 1 слайс
│   ├── dashboard
│   ├── places-list / place-create / place-detail / place-edit
│   ├── reviews-list
│   ├── subscription
│   ├── profile
│   ├── login
│   └── not-found
├── widgets/                   # композитные блоки
│   ├── app-shell              # layout (sidebar + bottom-nav)
│   ├── auth-shell             # layout для /login
│   ├── kpi-cards
│   ├── reviews-table
│   ├── place-form
│   ├── subscription-card
│   └── payments-history
├── features/                  # пользовательские действия
│   ├── auth-by-telegram
│   ├── create-place / update-place / toggle-place-activation / delete-place
│   ├── change-review-status
│   ├── extend-subscription
│   ├── update-profile / issue-telegram-code
├── entities/                  # бизнес-сущности
│   ├── session                # current owner, isAuthenticated
│   ├── place
│   ├── review
│   ├── analytics              # dashboard data
│   ├── subscription
│   └── payment
└── shared/
    ├── api/                   # httpClient + ensureCsrf
    ├── ui/                    # Button, Card, Input, Skeleton, EmptyState,
    │                          # ConfirmDialog, Sparkline
    ├── lib/
    └── types/
```

### Backend (Owner-relevant subset)

```
app/Interface/Http/
├── Controllers/Owner/
│   ├── Auth/{ExchangeOwnerLoginCodeController, LogoutOwnerController}.php
│   ├── OwnerSpaController.php           # отдаёт dist/owner/index.html
│   ├── OwnerMeController.php
│   ├── OwnerDashboardController.php
│   ├── OwnerPlacesController.php
│   ├── OwnerReviewsController.php
│   ├── OwnerSubscriptionController.php
│   ├── OwnerProfileController.php
│   └── Support/CurrentOwnerId.php       # достаёт OwnerId из request
├── Requests/Owner/                       # FormRequest'ы с toCommand()/toQuery()
└── Views/Owner/                          # array-DTO для JSON-ответов

app/Http/Middleware/
├── ResolveTenantBySubdomain.php
├── EnsureSubscriptionActive.php          # умеет `:402` через middleware-param
└── EnsureSessionMatchesTenant.php        # cross-tenant guard

app/Application/                          # use cases (см. § 5)
app/Domain/                               # агрегаты, VO, repositories (порты)
app/Infrastructure/Persistence/Eloquent/  # Reader'ы и Repository-импл'ы
```

---

## 4. API карта

Префикс `/api/owner` под middleware
`tenant + tenant-owns-session + auth:owner` (кроме `auth/exchange`).
**Платные мутации** дополнительно под `subscription.active:402`.

### Доступно всегда (если есть сессия и tenant)

| Метод | Путь                                    | Что делает                                          |
|-------|-----------------------------------------|-----------------------------------------------------|
| POST  | `/auth/exchange` (throttle 10/min)      | Обмен magic-кода на cookie-сессию (без `auth:owner`)|
| POST  | `/auth/logout`                          | Logout                                              |
| GET   | `/me`                                   | Текущий owner (OwnerMeView)                         |
| GET   | `/dashboard`                            | KPI 7д + daily series                               |
| GET   | `/features`                             | Список фич тарифа owner-а (`{ features: string[] }`) |
| GET   | `/places`                               | Список точек                                        |
| GET   | `/places/{id}`                          | Точка с QR и платформами                            |
| GET   | `/places/charge-preview`                | Pro-rata расчёт за добавление точки                 |
| GET   | `/reviews?status=&place_id=&from=&until=&page=&per_page=` | Отзывы paginated     |
| GET   | `/subscription`                         | Сводка подписки (тариф, срок, лимиты, сумма)        |
| POST  | `/subscription/init-payment`            | Создать платёж → `{payment_url}` (Tinkoff)          |
| GET   | `/payments?page=&per_page=`             | История платежей                                    |
| PATCH | `/profile`                              | name/email/subdomain (telegram_id неизменен)        |
| POST  | `/profile/telegram/issue-code` (5/min)  | Fresh magic-код для текущего привязанного Telegram  |

### Требует активной подписки (402 если истекла)

| Метод  | Путь                                  | Use case                          | Доп. guard                     |
|--------|---------------------------------------|-----------------------------------|--------------------------------|
| POST   | `/places`                             | RegisterPlace                     | `feature:multiple_places` (403)|
| PATCH  | `/places/{id}`                        | UpdatePlace                       | —                              |
| POST   | `/places/{id}/toggle`                 | ChangePlaceActivation             | —                              |
| DELETE | `/places/{id}`                        | DeletePlace                       | —                              |
| PATCH  | `/reviews/{id}/status`                | ChangeReviewStatus                | —                              |

**Порядок гардов на платных мутациях:** `auth:owner` → `subscription.active:402` → `feature:<key>`.
Сначала «оплата», потом «availability». Иначе пользователь без подписки получит 403
вместо корректного 402 «иди продли».

### Коды ошибок (`ApiErrorCode`)

`tenant_not_found`, `place_not_found`, `review_not_found`,
`subscription_expired`, `platform_not_found`, `login_code_invalid`,
`login_code_expired`, `login_code_already_consumed`,
`session_tenant_mismatch`, `owner_not_linked_to_telegram`,
`feature_not_available`.

Формат ответа на ошибку: `{ "message": "...", "code": "..." }`
(+ `errors: { field: [msg] }` на 422 от FormRequest).

---

## 5. Application use cases (Owner-relevant)

| Контекст | Use case                          | Тип           |
|----------|-----------------------------------|---------------|
| Iam      | `RequestOwnerLogin`               | Command       |
| Iam      | `ExchangeOwnerLoginCode`          | Command       |
| Iam      | `GetOwnerById`                    | Query         |
| Iam      | `GetOwnerSubscription`            | Query         |
| Iam      | `GetOwnerFeatures`                | Query         |
| Iam      | `UpdateOwnerProfile`              | Command       |
| Iam      | `CalculateSubscriptionAmount`     | Query         |
| Iam      | `CalculatePlaceCharge`            | Query         |
| Places   | `RegisterPlace` / `UpdatePlace`   | Command       |
| Places   | `ChangePlaceActivation`           | Command       |
| Places   | `DeletePlace`                     | Command       |
| Places   | `ListOwnerPlaces`                 | Query         |
| Places   | `GetPlaceForOwner`                | Query         |
| Reviews  | `ListOwnerReviews`                | Query (Reader)|
| Reviews  | `ChangeReviewStatus`              | Command       |
| Payments | `InitSubscriptionPayment`         | Command       |
| Payments | `ListOwnerPayments`               | Query (Reader)|

Use case = папка `Application/<Context>/<UseCase>/`, внутри —
`<UseCase>Command|Query.php`, `<UseCase>Handler.php`, опционально
`<UseCase>Result.php`/`<UseCase>View.php`/`Reader.php`.

---

## 6. Как добавить новую страницу/фичу

### Backend (DDD-цепочка)

1. **Domain.** Если нужны новые мутации — методы в агрегат + расширить
   Repository-интерфейс.
2. **Application.** Папка `<UseCase>/` (Command/Query + Handler). Чаще —
   переиспользовать существующее.
3. **Infrastructure.** Если новый Reader — реализация + bind в
   `<Context>ServiceProvider`.
4. **Interface HTTP.**
   - `routes/api.php` — добавить в группу
     `prefix('owner')->middleware([...])`. Платные мутации — под
     `subscription.active:402`.
   - `FormRequest` с правилами + helper-методом (`toCommand`/`toQuery`).
   - Контроллер 5–10 строк: command → handler → `response()->json()`/
     `ApiResponse::error(...)`.
5. **Feature-тест в `tests/Feature/Owner/`:**
   - happy path,
   - валидация (422),
   - cross-tenant 403/404 (чужой ресурс),
   - subscription expired 402 (если мутация платная),
   - feature_not_available 403 (если стоит `feature:<key>`),
   - auth required 401.

### Добавить feature-gate (паттерн)

1. **Domain.** Добавить case в `App\Domain\Iam\Feature` + строку в `label()`.
   Backing-value стабилен навсегда (контракт с БД); переименование = миграция данных.
2. **Filament.** Ничего не делать — `TariffForm` уже строит чекбоксы из `Feature::cases()`.
3. **Backend.** На нужной route'е: `->middleware('feature:<key>')`. На платной
   мутации — ПОСЛЕ `subscription.active:402` (см. порядок гардов в § 4).
4. **Frontend (owner).** `<FeatureGate feature="<key>">…</FeatureGate>` (опц. с
   `fallback={<UpsellCard …/>}`). Кеш фич владельца уже греется в `RequireSession`.
5. **Тест.** В `OwnerFeatureGuardTest` — 200 с фичей, 403 без, плюс приоритет
   402 над 403 при истёкшей подписке.
6. **Scan-side фича?** Добавить в `GetPublicPlaceViewHandler::SCAN_FEATURES` (whitelist)
   и расширить `ScanFeature` тип в `@guard-reviews/shared/types`. Owner-only фичи
   на публичный endpoint НЕ утекают — фильтруются именно этим whitelist'ом.

### Frontend (FSD-цепочка)

Идём сверху вниз; на каждом шаге добавляем слой, только если его ещё нет.

1. **`entities/<resource>/`** (новая бизнес-сущность):
   - `model/types.ts` — типы DTO.
   - `api/use<Resource>Query.ts` (+ `useMutation` для базовых operations).
   - `config/queryKeys.ts` — фабрика query-keys.
   - `ui/<Resource>Card.tsx` — визуальный примитив (опц.).
   - `index.ts` — public API.
2. **`features/<verb-noun>/`** (действие: `update-profile`,
   `change-review-status`):
   - `api/use<Action>Mutation.ts` — мутация + invalidate / optimistic update.
   - `lib/` — pure-функции (валидация, форматтеры).
   - `ui/<Action>...tsx`.
   - `index.ts`.
3. **`widgets/<composite>/`** — несколько фичей+entities в переиспользуемом блоке.
4. **`pages/<route>/`** — тонкая страница, собирает widgets из entity-данных.
5. **`app/router/`** — маршрут.
6. **Тест** в `<slice>/ui/X.test.tsx` или `<slice>/api/X.test.ts`:
   - mock `httpClient` через `vi.spyOn` или `axios-mock-adapter`;
   - mock `ensureCsrf` через `vi.mock('@/shared/api', ...)`;
   - mock `sonner` через `vi.mock('sonner', () => ({ toast: ... }))`.

---

## 7. Эксплуатация

### Локальная разработка

```bash
# Backend (php artisan + queue + pail + vite scan)
cd backend && composer dev

# Owner SPA отдельно (нужен только если редактируем owner)
cd frontend && npm run dev:owner   # → http://localhost:5174

# Тесты:
cd backend && composer test
cd frontend/owner && npm run test && npm run lint && npm run typecheck
```

В dev SPA проксирует `/api`, `/sanctum` → `http://127.0.0.1:8000` (см.
`vite.config.ts`). Чтобы попасть в кабинет конкретного тенанта на dev,
шлите `X-Tenant-Slug` (см. `TenantResolver`).

### Production (Docker)

1. `make prod-up` собирает образы и поднимает стек.
   - `php`-image содержит `dist/owner/index.html` рядом с backend
     (multi-stage build с node:22 — см. `docker/php/Dockerfile`).
   - `nginx`-image содержит `dist/owner/` в `/var/www/frontend/owner/`.
2. nginx отдаёт `/owner/assets/*` напрямую (30d cache), всё остальное под
   `/owner/` идёт в Laravel → `OwnerSpaController` рендерит SPA-shell
   (с tenant-резолвом по поддомену).
3. Caddy (`deploy/proxy/`) — TLS-терминатор; маршрутизирует `*.otziv.space`
   на nginx нужной среды (staging/prod).

### CI (GitHub Actions, `.github/workflows/ci.yml`)

- Backend job: PHP 8.4 + PostgreSQL 16 + Redis 7, прогон `pint --test` +
  `php artisan test`.
- Frontend job: Node 22, прогон `npm run lint/typecheck/test/build:owner`
  внутри workspace.

---

## 8. Безопасность

- **CSRF:** Sanctum cookie + `withCredentials`; `ensureCsrf()` перед
  любой мутацией (вшито в feature-mutation hooks).
- **Cross-tenant:** `EnsureSessionMatchesTenant` middleware проверяет, что
  cookie-сессия принадлежит owner'у запрошенного поддомена; иначе logout
  + 403 `session_tenant_mismatch`.
- **Subscription guard:** `EnsureSubscriptionActive` middleware с
  параметром-статусом. Для owner-панели — `:402` (Payment Required), для
  public scan API — дефолт 403 (исторический контракт).
- **404 вместо 403** для чужих ресурсов — не раскрываем существование ID
  (см. `ChangeReviewStatusResult::NotOwnedByCaller` → 404).
- **Frontend 402-interceptor** (`shared/api/httpClient.ts`): при 402
  редиректит на `/owner/subscription` (если ещё не там) → пользователь
  может оплатить, не разбираясь.
- **Profile guard:** `PATCH /profile` НЕ принимает `telegram_id`/`tariff_id`;
  контроллер пробрасывает текущие значения, чтобы owner случайно не
  отвязал Telegram через форму профиля.
- **Rate limits:** `auth/exchange` — 10/min; `profile/telegram/issue-code`
  — 5/min.

---

## 9. Подводные камни (накопительно по фазам 0–7)

- `ensureCsrf()` в vitest — мокать через `vi.mock('@/shared/api', ...)`
  (иначе реальный axios-запрос к `/sanctum/csrf-cookie`).
- `sonner` в vitest — мокать `vi.mock('sonner', ...)`.
- `Tariff::placesLimit` — `?int`, не забыть в `TariffMapper` при
  изменении VO.
- `Money` валидирует `> 0` в конструкторе — `CalculateSubscriptionAmount`
  должен вернуть положительное число (есть env fallback).
- `AcquirerGateway::isConfigured()` — на проде true, в тестах биндим
  `fakeAcquirerGateway()` через `$this->app->bind(...)`.
- **Смена `subdomain_slug`** инвалидирует cookie (cookie привязана к
  старому поддомену). UI показывает предупреждение, редирект — на стороне
  пользователя (открыть новый адрес).
- Filament v5 + Laravel 13 (исторический admin-context) — отдельная панель
  `/admin`, не путать с Owner-панелью.
- Owner SPA-bundle в production должен быть в **php-image** (для
  `OwnerSpaController`) и в **nginx-image** (для статики assets). Это
  закрыто двойным `COPY --from=...frontend /dist/owner` в Dockerfile'ах
  (см. § 7).

---

## 10. Чек-лист «добавить мутацию X»

1. Backend: handler с тестом → controller-метод → FormRequest → route.
   Под `subscription.active:402` если функция платная.
2. Feature-тест: happy + 401 + 422 + cross-tenant 404 (если ресурс) + 402
   (если платная).
3. Frontend: `useXxxMutation` в `features/<x>/api/`,
   `ensureCsrf()` + `httpClient.<method>(...)`, инвалидация/optimistic
   update в `onMutate`/`onSettled`. Поля-ошибки 422 → `name-tag` Error +
   type guard.
4. UI-компонент в `features/<x>/ui/`, импортируем из widget'ов через
   public API.
5. vitest на mutation: моки `@/shared/api` и `sonner`,
   `QueryClient` локально.
6. Pint + lint + typecheck + test.
7. Обновить `backend/docs/owner-panel.md` § 4–5 если добавили новый
   endpoint/use case.
