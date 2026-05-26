# Owner Panel — план реализации

Документ-handoff для следующих сессий. Цель — личный кабинет владельца
заведения, отдельно от dev-админки (Filament). Стиль — **Kvell Merchant**:
светлая палитра, мягкие тени, крупная типографика, плавные переходы,
mobile-first. Полностью адаптирован под телефоны и десктопы.

Связанные документы (читать сначала):
- `backend/саммари.md` — DDD-карта проекта.
- `backend/docs/admin.md` — устройство dev-админки (что мы НЕ повторяем).
- `backend/админка-план.md` — историческое описание фаз 0–6.

---

## Статус

| Фаза | Статус | Краткое содержание |
|------|--------|--------------------|
| 0 | ✅ сделано | React-SPA каркас + backend SPA-route + Sanctum guard `owner` + 5 тестов |
| 0.5 | ✅ сделано | Миграция фронта в FSD + ESLint-правила (FSD-границы, no-class, no-direct-fetch), shared/ui (Button/Card/Input), once-memoized ensureCsrf. См. § 1.6. |
| 1 | ✅ сделано | Auth через Telegram magic-code: domain `OwnerLoginRequest`, handlers `RequestOwnerLogin`/`ExchangeOwnerLoginCode`, миграция `owner_login_requests`, команда `/login`, HTTP `/auth/exchange`+`/auth/logout`+`/me`, middleware `EnsureSessionMatchesTenant`, frontend `entities/session` + `features/auth-by-telegram` + `RequireSession` guard. Тесты: 7 domain, 6 application, 7 feature, 4 frontend. |
| 2 | ✅ сделано | Dashboard + просмотр: `OwnerDashboardReader` (KPI 7d + daily series), `OwnerReviewsReader` (фильтры status/place/date + пагинация), HTTP `/dashboard`/`/places`/`/places/{id}`/`/reviews`, frontend `entities/{place,review,analytics}` + widgets `kpi-cards`/`reviews-table` + страницы dashboard/places-list/place-detail/reviews-list. Тесты: 13 feature, 7 frontend. |
| 3 | ✅ сделано | Управление точками + per-place ценообразование: `tariffs.extra_place_price` (kopecks) редактируется в Filament, `Tariff` VO с basePrice/extraPlacePrice, `CalculateSubscriptionAmount` берёт из тарифа owner'а (fallback на env), новый `CalculatePlaceCharge` (pro-rata за оставшиеся дни + `monthly_delta` для предупреждения о след. месяце). HTTP `POST/PATCH/DELETE /places`, `POST /places/{id}/toggle`, `GET /places/charge-preview` с ownership-guards через `GetPlaceForOwner`. Frontend `features/{create-place,update-place,toggle-place-activation,delete-place}` + `widgets/place-form` + страницы place-create/place-edit + `ChargePreviewBanner`. Тесты: 5 unit, 9 feature CRUD, 3 frontend banner. |
| **4** | 🟡 **следующая** | Управление отзывами (смена статуса) |
| 5–8 | ⏳ запланировано | см. ниже |

### Что уже сделано в Фазе 0

**Frontend (`frontend/owner/`):**
- React 18 + Vite + TypeScript + Tailwind + React Query + Zustand + sonner + lucide-react.
- PWA (vite-plugin-pwa) с manifest, scope `/owner/`, navigateFallback на index.html, runtime cache для `/api/owner/me`.
- Kvell-токены в `tailwind.config.ts` (`canvas`, `surface`, `ink.*`, `accent`, `danger`, `warning`, `shadow-soft`, `rounded-2xl`).
- Лейауты: `AppShell` (sidebar на ≥lg, плавающий bottom-nav на мобиле) + `AuthLayout`.
- Маршруты-заглушки: `/`, `/places`, `/reviews`, `/subscription`, `/profile`, `/login`, 404.
- axios-клиент с `withCredentials` + `ensureCsrf()` helper для Sanctum.
- Zustand auth-store (заглушка для Фазы 1).
- Smoke-тесты `App.test.tsx` (vitest + RTL): dashboard / login / 404.
- Корневой `frontend/package.json` обновлён: `dev:owner`, `build:owner`, `test` через workspaces.

**Backend:**
- `OwnerSpaController` (`app/Interface/Http/Controllers/Owner/`) отдаёт `dist/owner/index.html` под middleware `tenant`. Без живого тенанта — 404.
- `routes/web.php` — `Route::middleware('tenant')->get('/owner/{any?}', OwnerSpaController::class)` с catch-all для SPA fallback.
- `OwnerMeController` (`/api/owner/me`) — заглушка, возвращает 401 пока auth не подключён (Фаза 1).
- `routes/api.php` — группа `Route::prefix('owner')->middleware('tenant')` с заглушкой `me`.
- `config/auth.php` — новый guard `owner` (driver=session, provider=users).
- `config/sanctum.php` — `'guard' => ['owner', 'web']` для cookie-auth.
- `config/cors.php` создан: `supports_credentials=true`, `CORS_ALLOWED_ORIGINS` из env (CSV).
- `.env.example` — добавлены `SANCTUM_STATEFUL_DOMAINS` (+ `*.otziv.space`) и `CORS_ALLOWED_ORIGINS`.
- Feature-тесты `tests/Feature/Owner/OwnerSpaTest.php` (5 тестов, 9 assertions):
  - SPA-shell под валидным тенантом → 200 + HTML.
  - Catch-all `/owner/anything/here` тоже отдаёт shell.
  - Без тенанта → 404.
  - Без собранного бандла → 404 с понятным сообщением.
  - `/api/owner/me` без сессии → 401.

### Куда смотреть в Фазе 0.5 (миграция в FSD) — **сделать первой**

1. Настроить ESLint: `eslint-plugin-boundaries` + `no-restricted-syntax` (запрет `class`)
   + `no-restricted-imports` (запрет `axios`/`fetch` вне `shared/api/` и `<slice>/api/`).
   См. §§ 1.5, 1.6.
2. Перенести существующие файлы по таблице в § 1.6.
3. Завести `index.ts` в каждом созданном слайсе.
4. Вынести `.card-padded` / `.btn-primary` из CSS-слоёв в `shared/ui/` как
   `<Card>`/`<Button>` компоненты.
5. Заменить module-level `let csrfFetched` в `shared/api/httpClient.ts` на
   мемоизированный once-closure.

### Куда смотреть в Фазе 1 (после Фазы 0.5)

1. Telegram bot — добавить команду `/login` в `app/Interface/TelegramBot/Commands/`.
2. Domain `OwnerLoginRequest` — новый агрегат в `app/Domain/Iam/`.
3. Application `RequestOwnerLogin` / `ExchangeOwnerLoginCode` — два use case'а в `app/Application/Iam/`.
4. Frontend (уже в FSD после 0.5):
   - `entities/session/api/useSessionQuery.ts` — GET /api/owner/me через React Query.
   - `features/auth-by-telegram/api/useExchangeCodeMutation.ts` — обмен code на сессию.
   - `pages/login/ui/LoginPage.tsx` — композит kvell-карточки + `features/auth-by-telegram` блоков.
   - `app/router/guards/RequireSession.tsx` — guard через `useSessionQuery`, **не** через прямой fetch.
5. Tenant-cross-check middleware (`EnsureSessionMatchesTenant`) подключить к группе `/api/owner/*`.

---

## 0. Фиксированные решения

| Параметр       | Решение                                                         |
|----------------|-----------------------------------------------------------------|
| URL            | `{slug}.otziv.space/owner` (poddomen владельца, см. `tenant` middleware) |
| Frontend       | **React 18 SPA + Vite + TypeScript + Tailwind + React Query + Zustand**, PWA |
| Frontend архитектура | **Feature-Sliced Design (строго).** Слои: `app/` → `pages/` → `widgets/` → `features/` → `entities/` → `shared/`. Только нисходящие импорты. См. п. 0.1. |
| Парадигма      | **Только функциональное программирование.** Никаких ES6-классов. Состояние — в hooks/stores. Сервисные функции — pure где возможно, побочные эффекты — изолированные модули. |
| Server state   | **`useQuery` / `useMutation` (TanStack Query) — единственный способ ходить в API.** Прямые `fetch`/`axios` в компонентах и страницах запрещены. Запросы только через `entities/<x>/api/` и `features/<x>/api/`, экспортирующих React Query hooks. |
| Client state   | Zustand store'ы в `entities/<x>/model/` или `features/<x>/model/`. Реакт-локальный state (`useState`) — только если данные не нужны вне компонента. |
| Расположение   | `frontend/owner/` (соседствует с `frontend/scan/`)               |
| Auth           | Magic-link через Telegram-бот: команда `/login` → одноразовый code → SPA меняет на Sanctum cookie |
| Guard          | Новый Laravel guard `owner` поверх таблицы `users` (Sanctum SPA-сессия, **не** PAT) |
| API            | `/api/owner/*` под middleware `tenant` + `auth:owner` + `subscription.active` (там где нужно) |
| Стиль          | Kvell Merchant: бежево-белый фон, soft shadows, rounded-2xl, sans-serif (Inter/Manrope), accent — мягкий зелёный/коралл |
| Иконки         | Lucide React                                                    |
| Тестирование   | Backend — Pest (Feature + Unit). Frontend — Vitest + React Testing Library |

**Принципы:**
- Тонкий API-слой: HTTP-контроллер → Command → Application Handler (DDD-правила те же).
- Никаких новых Eloquent-моделей в чужих контекстах. Все мутации — через существующие use cases.
- Тенант (Owner) резолвится из поддомена (уже работающим `ResolveTenantBySubdomain`).
- Текущий аутентифицированный owner = `auth('owner')->user()` Eloquent `User`, идентификатор которого совпадает с резолвленным тенантом (cross-check в middleware).

---

## 0.1. Frontend архитектура — Feature-Sliced Design (строго)

Канон: https://feature-sliced.design/. Применяется ко всему `frontend/owner/`.

### Слои (сверху вниз, импорт строго НИЖЕ)

```
src/
├── app/        # composition root: providers, router, styles, PWA-bootstrap
├── pages/      # одна страница ≈ один маршрут (DashboardPage, PlacesListPage, ...)
├── widgets/    # композитные блоки UI: AppShell, BottomNav, Sidebar, ReviewsTable
├── features/   # пользовательская функциональность: auth-by-telegram, toggle-place-activation
├── entities/   # бизнес-сущности: place, review, owner, subscription, session
└── shared/     # ui-kit, lib, api-клиент, config, типы — ничего бизнес-специфичного
```

**Правила импортов (enforced ESLint'ом, см. п. 8.1):**
- `app/` может импортировать всё.
- `pages/` импортирует из `widgets`, `features`, `entities`, `shared`.
- `widgets/` импортирует из `features`, `entities`, `shared`.
- `features/` импортирует из `entities`, `shared`.
- `entities/` импортирует только из `shared`.
- `shared/` ни от чего не зависит.
- **Внутри слоя слайсы НЕ импортируют друг друга** (например, `entities/place` не видит `entities/review` — только если совсем-совсем не обойтись, и только через public API).

### Сегменты слайса

Каждый слайс (`pages/dashboard`, `entities/place`, `features/toggle-place-activation`, …) имеет до пяти сегментов:

```
slice/
├── ui/         # React-компоненты
├── model/      # zustand-store, useXxx-хуки бизнес-состояния
├── api/        # React Query hooks: useXxxQuery, useXxxMutation
├── lib/        # чистые helpers (форматтеры, валидаторы)
├── config/     # константы, query-keys
└── index.ts    # ПУБЛИЧНОЕ API слайса. Импорт извне — только через index.
```

### Server state — только React Query

- **Никаких прямых `fetch`/`axios.get` в компонентах/страницах.** Любой сетевой вызов оборачивается в `useXxxQuery` / `useXxxMutation` и живёт в `<slice>/api/`.
- Query-keys — фабрики в `<slice>/config/queryKeys.ts` (тип-безопасные, ненулевая длина).
- Инвалидация после мутации — через `queryClient.invalidateQueries({ queryKey: placesQueryKeys.list() })`.
- HTTP-клиент один — `shared/api/httpClient.ts` (axios c `withCredentials`, ensureCsrf). Хуки используют его внутри, наружу — только React Query API.

### FP-only (без классов)

- ES6-классы запрещены (`class Foo {}`) — ESLint правило `no-restricted-syntax: ClassDeclaration`. Исключения: внешние библиотеки, которые сами требуют классы (в `node_modules` — ОК; в нашем коде — нет).
- Доменные сущности на фронте — типы и pure-функции:
  ```ts
  export type Place = { id: PlaceId; title: string; isActive: boolean };
  export const isPlaceActive = (p: Place): boolean => p.isActive;
  ```
- Состояние — Zustand-сторы (это функции/замыкания) + React Query.
- Сервисы (форматтеры, валидаторы, парсеры) — pure-функции в `<slice>/lib/`.
- Побочные эффекты (localStorage, document, navigator) — изолированы в `<slice>/lib/` или `shared/lib/`, не зашиты в компоненты.

### Public API через `index.ts`

Каждый слайс экспортирует наружу только то, что описано в `index.ts`. Импорт глубоких путей запрещён ESLint'ом:

```ts
// ❌ нельзя
import { PlaceCard } from '@/entities/place/ui/PlaceCard';

// ✅ можно
import { PlaceCard } from '@/entities/place';
```

### Skeleton (как это будет выглядеть к концу всех фаз)

```
src/
├── app/
│   ├── providers/         # QueryClientProvider, RouterProvider, ThemeProvider
│   ├── router/            # BrowserRouter + routes
│   ├── styles/            # tailwind base + globals
│   └── index.tsx          # createRoot + composition
├── pages/
│   ├── dashboard/
│   ├── places-list/
│   ├── place-detail/
│   ├── reviews-list/
│   ├── subscription/
│   ├── profile/
│   ├── login/
│   ├── not-found/
│   └── index.ts
├── widgets/
│   ├── app-shell/         # sidebar + bottom-nav layout
│   ├── auth-shell/        # лэйаут для /login
│   ├── kpi-cards/
│   ├── reviews-table/
│   └── index.ts
├── features/
│   ├── auth-by-telegram/  # login flow
│   ├── logout/
│   ├── toggle-place-activation/
│   ├── change-review-status/
│   ├── extend-subscription/
│   ├── update-profile/
│   └── index.ts
├── entities/
│   ├── session/           # current owner, isAuthenticated
│   ├── place/             # types, useXxxQuery, ui/PlaceCard
│   ├── review/
│   ├── subscription/
│   ├── payment/
│   └── index.ts
└── shared/
    ├── api/               # httpClient, ensureCsrf
    ├── config/            # env, routes constants
    ├── lib/               # date, format, validation
    ├── ui/                # Button, Card, Input, Badge — kvell-tokens
    ├── types/             # глобальные branded-id и т. п.
    └── index.ts
```

---

## 1. Фаза 0 — фундамент (frontend + auth-инфраструктура)

### 1.1. Frontend scaffold (`frontend/owner/`)

> ⚠️ **Текущий каркас Фазы 0 НЕ соответствует FSD** (см. п. 1.6 ниже —
> «Что нужно исправить»). Целевая структура для Фазы 0.5 — ниже.

Целевой каркас:
```
frontend/owner/
├── index.html
├── vite.config.ts          # alias '@', proxy /api → backend, PWA-plugin
├── tsconfig.json           # alias paths: @/app, @/pages, @/widgets, @/features, @/entities, @/shared
├── tailwind.config.ts      # Kvell-токены: цвета, тени, радиусы, шрифт
├── postcss.config.js
├── eslint.config.js        # eslint-plugin-boundaries (FSD-границы) + no-class-rule
├── package.json
└── src/
    ├── app/                # composition root
    │   ├── providers/      # QueryClientProvider, RouterProvider, ToasterProvider
    │   ├── router/         # routes + BrowserRouter (basename=/owner)
    │   ├── styles/         # tailwind base + globals
    │   └── index.tsx       # createRoot
    ├── pages/              # 1 route = 1 page-слайс
    ├── widgets/            # AppShell (sidebar+bottom-nav), AuthShell, KpiCards, ...
    ├── features/           # auth-by-telegram, toggle-place-activation, ...
    ├── entities/           # session, place, review, subscription, payment
    └── shared/
        ├── api/            # httpClient (axios+withCredentials), ensureCsrf
        ├── config/         # env, routes constants
        ├── lib/            # date, format, validation, branded ids
        ├── ui/             # Button, Card, Input, Badge — kvell-стиль
        └── types/
```

**Design tokens (Kvell-style):**
- Фон: `bg-[#FAFAF7]` (тёплый off-white)
- Карточки: `bg-white shadow-[0_1px_3px_rgba(15,23,42,.04),_0_8px_24px_rgba(15,23,42,.04)] rounded-2xl`
- Текст: `text-slate-900` для заголовков, `text-slate-600` для подписей
- Accent: мягкий зелёный `#10B981` (success/CTA) + коралл `#F97066` (negative/danger)
- Шрифт: Inter / Manrope (через @fontsource)
- Spacing: щедрый — `p-6` карточки, `space-y-4` блоки, `gap-3` иконки

**Mobile-first:**
- Базовая ширина — 360–414px, скейлится через Tailwind `sm:`, `md:`, `lg:`.
- Bottom-nav на мобиле (4 пункта: Главная, Точки, Отзывы, Профиль).
- Sidebar на десктопе (≥ lg).

**PWA:**
- `vite-plugin-pwa` с manifest (name, icons 192/512, theme_color `#FAFAF7`).
- `registerType: 'autoUpdate'`, runtime cache для `/api/owner/me` и т. п.
- Установка через A2HS поддержана iOS Safari / Android Chrome.

### 1.2. Backend: монтирование SPA + tenant-routing

- **Маршрут:** `routes/web.php` — `Route::middleware('tenant')->get('/owner/{any?}', OwnerSpaController::class)->where('any', '.*');`
- `OwnerSpaController::__invoke()` — отдаёт собранный `frontend/owner/dist/index.html`. Asset path берётся через Vite manifest.
- На staging/prod — статика собирается отдельно, контроллер просто отдаёт `index.html`. SPA сам разруливает routing.
- Docker: добавить step в Dockerfile для билда `frontend/owner/dist`.

### 1.3. Backend: Sanctum SPA + новый guard `owner`

- `config/auth.php` — добавить guard `owner` (driver=session, provider=users).
- `config/sanctum.php` — `stateful` дополнить `{slug}.otziv.space` маской через env `SANCTUM_STATEFUL_DOMAINS`.
- `bootstrap/app.php` — middleware группа `web` для маршрутов owner-панели (cookies/session/csrf).
- `config/cors.php` — разрешить cookies, origin = поддомены `otziv.space`.

### 1.4. Тесты

- Feature: `OwnerSpaTest` — `/owner` отдаёт SPA-shell под живым тенантом, иначе 404.
- Готово.

### 1.5. ESLint конфигурация FSD-границ (Фаза 0.5)

Подключить в `frontend/owner/`:
- `eslint-plugin-boundaries` — запрещает импорты «вверх» по слоям и кросс-слайс импорты.
- `eslint-plugin-import` — настройка `no-restricted-paths` дополнительно к boundaries.
- `no-restricted-syntax: ClassDeclaration` (свой rule) — запрещает классы во всём `src/` нашего кода.
- `no-restricted-imports` — `axios`/`fetch` из компонентов запрещены; только через `shared/api/httpClient` (но и то — через React Query hooks).

Пример конфигурации границ:
```js
// eslint.config.js
import boundaries from 'eslint-plugin-boundaries';

export default [{
  plugins: { boundaries },
  settings: {
    'boundaries/elements': [
      { type: 'app',      pattern: 'src/app/**' },
      { type: 'pages',    pattern: 'src/pages/**' },
      { type: 'widgets',  pattern: 'src/widgets/**' },
      { type: 'features', pattern: 'src/features/**' },
      { type: 'entities', pattern: 'src/entities/**' },
      { type: 'shared',   pattern: 'src/shared/**' },
    ],
  },
  rules: {
    'boundaries/element-types': ['error', {
      default: 'disallow',
      rules: [
        { from: 'app',      allow: ['pages', 'widgets', 'features', 'entities', 'shared'] },
        { from: 'pages',    allow: ['widgets', 'features', 'entities', 'shared'] },
        { from: 'widgets',  allow: ['features', 'entities', 'shared'] },
        { from: 'features', allow: ['entities', 'shared'] },
        { from: 'entities', allow: ['shared'] },
        { from: 'shared',   allow: [] },
      ],
    }],
  },
}];
```

### 1.6. Что нужно исправить в текущем каркасе (Фаза 0.5 — миграция в FSD)

Каркас, заскаффолженный в Фазе 0, **функционально работает** (тесты зелёные),
но структурно НЕ соответствует FSD. Перед стартом Фазы 1 надо привести его
к канону. Конкретный план миграции:

| Сейчас                                       | Перенести в                                                |
|----------------------------------------------|------------------------------------------------------------|
| `src/main.tsx`                               | `src/app/index.tsx`                                        |
| `src/App.tsx`                                | разделить: BrowserRouter+routes → `src/app/router/`; провайдеры (QueryClient/Toaster) → `src/app/providers/` |
| `src/styles/index.css`                       | `src/app/styles/index.css`                                 |
| `src/layouts/AppShell.tsx`                   | `src/widgets/app-shell/ui/AppShell.tsx` + `index.ts`       |
| `src/layouts/AuthLayout.tsx`                 | `src/widgets/auth-shell/ui/AuthShell.tsx` + `index.ts`     |
| `src/features/dashboard/DashboardPage.tsx`   | `src/pages/dashboard/ui/DashboardPage.tsx` + `index.ts`    |
| `src/features/auth/LoginPage.tsx`            | `src/pages/login/ui/LoginPage.tsx` + `index.ts`            |
| `src/features/system/PlaceholderPage.tsx`    | `src/pages/placeholder/ui/PlaceholderPage.tsx` + `index.ts` (или удалить и сделать per-stub в каждой странице) |
| `src/features/system/NotFoundPage.tsx`       | `src/pages/not-found/ui/NotFoundPage.tsx` + `index.ts`     |
| `src/api/client.ts`                          | `src/shared/api/httpClient.ts` + `src/shared/api/index.ts` |
| `src/lib/auth.ts` (Zustand owner-стор)       | `src/entities/session/model/sessionStore.ts` + `src/entities/session/index.ts` |
| `src/App.test.tsx`                           | `src/app/index.test.tsx` (или per-page тесты в `pages/*/ui/*.test.tsx`) |

Дополнительно сделать:
1. **Алиасы в `tsconfig.json` и `vite.config.ts`:** добавить `@/app/*`,
   `@/pages/*`, `@/widgets/*`, `@/features/*`, `@/entities/*`, `@/shared/*`
   (текущий универсальный `@/*` оставить совместимости ради на время миграции).
2. **`index.ts` в каждом слайсе** — public API. Запретить deep-import'ы через
   ESLint (`no-restricted-imports` patterns: `@/entities/*/ui/*`, `@/features/*/model/*` и т. п.).
3. **`eslint.config.js`** — настроить boundaries (см. п. 1.5).
4. **CSS-классы из `@layer components`** (`.card-padded`, `.btn-primary`, ...) —
   перенести в `shared/ui` как настоящие React-компоненты (`<Card>`, `<Button variant="primary">`).
   Это уберёт магические строки из страниц и зафиксирует kvell-токены в одном месте.
5. **`api/client.ts` → `shared/api/httpClient.ts`:** убрать module-level `let csrfFetched`
   (mutable singleton). Заменить на closure-фабрику или мемоизированный promise
   (`memoizedEnsureCsrf = once(...)`) — это и FP-чище, и тестируемее.
6. **Никаких прямых вызовов в страницах.** Любой будущий вызов `/api/owner/me` — это
   `useSessionQuery()` из `entities/session/api/`, не `api.get(...)` в `useEffect`.

В коде каркаса **уже нет ES6-классов** — это правило выполняется. ESLint его
зафиксирует на будущее.

---

## 2. Фаза 1 — Auth через Telegram (magic code)

### 2.1. Domain

Новый bounded context `Auth` (мини-контекст внутри `Iam` или отдельный — по аналогии с `Admin`):

- `Domain/Iam/OwnerLoginRequest` — агрегат:
  - `OwnerLoginRequestId` (UUID VO)
  - поля: `telegramId`, `code` (6 цифр), `ownerId?`, `expiresAt`, `consumedAt?`
  - `static issue(...)`, `consume(Clock)`, `isExpired(Clock)`.
- `OwnerLoginRequestRepository::save/findActiveByCode(string)/findById`.

### 2.2. Application

- `Application/Iam/RequestOwnerLogin/`:
  - `RequestOwnerLoginHandler` принимает `telegram_id`, находит owner'а по `getOwnerByTelegram`, создаёт `OwnerLoginRequest` с 6-значным кодом и TTL 10 мин, возвращает code (для бота).
- `Application/Iam/ExchangeOwnerLoginCode/`:
  - `ExchangeOwnerLoginCodeHandler` принимает `code`, валидирует (не истёк, не consumed), помечает consumed, возвращает `OwnerId` для последующего `Auth::login()`.
- Exceptions: `LoginCodeNotFound`, `LoginCodeExpired`, `LoginCodeAlreadyConsumed`.

### 2.3. Infrastructure

- Миграция `owner_login_requests` (id uuid, owner_id uuid FK, telegram_id, code, expires_at, consumed_at, created_at). Index на `code` + `expires_at`.
- `Eloquent*Repository`, `Mapper`, `UuidIdGenerator`.

### 2.4. Interface

- Telegram bot: новая команда `/login`:
  - Проверяет, что чат привязан к owner'у (через существующий `GetOwnerByTelegram`).
  - Дёргает `RequestOwnerLoginHandler`.
  - Отвечает: «Открой панель и введи код **123456**. Или нажми кнопку:» + InlineKeyboard с url-кнопкой `https://{slug}.otziv.space/owner/login?code=123456`.
- HTTP endpoint `POST /api/owner/auth/exchange` (без auth-middleware, с `tenant`):
  - Validates `code` (6 digits regex), throttled `5,1`.
  - Дёргает `ExchangeOwnerLoginCodeHandler` → получает `OwnerId`.
  - Дополнительный cross-check: owner.subdomain_slug === tenant.subdomain_slug (анти-CSRF между тенантами).
  - `Auth::guard('owner')->loginUsingId($ownerId)` + установка cookie сессии.
  - Возвращает `{user: OwnerMeView}`.
- HTTP endpoint `POST /api/owner/auth/logout` — `Auth::guard('owner')->logout()` + invalidate session.

### 2.5. Frontend (FSD)

- `entities/session/`:
  - `api/useSessionQuery.ts` — `useQuery({ queryKey: ['session','me'], queryFn: fetchMe })`.
  - `api/useLogoutMutation.ts`.
  - `model/sessionStore.ts` — производное состояние (`isAuthenticated`, `currentOwner`) — опционально, если нужно вне React Query кеша.
- `features/auth-by-telegram/`:
  - `api/useExchangeCodeMutation.ts` — `useMutation` на `POST /api/owner/auth/exchange`. На onSuccess → `queryClient.setQueryData(['session','me'], data)`.
  - `lib/buildTelegramDeepLink.ts` — pure-функция, собирает `https://t.me/{bot}?start=login`.
  - `ui/TelegramLoginCta.tsx`, `ui/CodeForm.tsx` — компоненты-feature, тонкие.
- `pages/login/ui/LoginPage.tsx` — композит `widgets/auth-shell` + двух features-блоков.
- `app/router/` — guard-обёртка `<RequireSession>`: внутри использует `useSessionQuery` и редиректит на `/login` если 401. **Никаких прямых fetch'ей в guard'ах.**

**Запреты в Фазе 1:**
- `axios.post('/api/owner/auth/exchange')` в компонентах. Только `useExchangeCodeMutation()`.
- Прямой `useEffect(() => { fetch('/api/owner/me') })` для проверки сессии. Только `useSessionQuery()`.

### 2.6. Тесты

- Domain: `OwnerLoginRequestTest` — TTL, consumption, повторное использование запрещено.
- Application: `RequestOwnerLoginHandlerTest`, `ExchangeOwnerLoginCodeHandlerTest`.
- Feature: `/api/owner/auth/exchange` happy path / истёкший код / consumed код / wrong tenant / throttle.
- Bot: `/login`-команда → отвечает с inline-кнопкой.

---

## 3. Фаза 2 — Dashboard + просмотр (places + reviews)

### 3.1. Backend API (read-only)

Все под `middleware(['tenant', 'auth:owner', 'tenant-owns-session'])` где последний — новый middleware (cross-check session owner = subdomain tenant; если разные — 403).

- `GET /api/owner/me` → `{id, name, email, subdomain, telegramConnected, tariff: {...}, subscription: {endsAt, daysLeft, isActive}}`
- `GET /api/owner/dashboard` → KPI за 7 дней: `{scans, reviews, negative, redirects, weeklySeries: [...]}`
  - Берём через `WeeklySummaryReader` (уже есть).
- `GET /api/owner/places` → `[{id, title, isActive, platformsCount, qrScanUrl, createdAt}]`
  - Через `ListOwnerPlaces` use case (уже есть).
- `GET /api/owner/places/{id}` → детальный view с QR PNG-url и платформами.
  - Через `GetPlaceForOwner`.
- `GET /api/owner/reviews?status=&place_id=&from=&until=&page=` → paginated + filters.
  - Новый Reader `OwnerReviewsReader` (или расширить `RecentReviewsReader`).

### 3.2. Frontend (FSD)

- `entities/place/api/useOwnerPlacesQuery.ts`, `entities/place/api/usePlaceQuery.ts`.
- `entities/review/api/useOwnerReviewsQuery.ts` (с фильтрами, ключ `['reviews', filters]`, `keepPreviousData: true`).
- `entities/place/ui/PlaceCard.tsx`, `entities/review/ui/ReviewCard.tsx` — visual primitives без сетевых вызовов.
- `widgets/kpi-cards/` — композит карточек KPI; читает данные через `useDashboardQuery` (в `entities/analytics/api/`).
- `widgets/reviews-table/` — фильтры + список; ничего не знает про конкретные endpoint'ы, получает `data` props.
- `pages/dashboard/`, `pages/places-list/`, `pages/place-detail/`, `pages/reviews-list/` — собирают widgets из entity-данных.
- Спарк-линия за неделю — `shared/ui/Sparkline.tsx` (тонкая обёртка над recharts).

**Запреты в Фазе 2:**
- Прямые `axios.get('/api/owner/places')` в страницах. Только `useOwnerPlacesQuery()`.
- Дублирование query-keys в разных файлах. Все ключи — в `entities/<x>/config/queryKeys.ts`.

### 3.3. Тесты

- Feature: каждый GET endpoint — auth required, фильтрация работает, чужой owner не видит чужих данных.
- Frontend: snapshot key components, React Query happy path.

---

## 4. Фаза 3 — Управление точками

### 4.1. Backend API

- `POST /api/owner/places` → `RegisterPlaceHandler` (уже есть).
- `PATCH /api/owner/places/{id}` → `UpdatePlaceHandler` (уже есть).
- `POST /api/owner/places/{id}/toggle` → `ChangePlaceActivationHandler` (уже есть).
- `DELETE /api/owner/places/{id}` → `DeletePlaceHandler` (уже есть).
- `GET /api/owner/places/{id}/qr.png` → стрим PNG через `QrCodeService` (уже есть).

Все мутации `Authorize::isPlaceOf(currentOwner)` — guard в FormRequest.

### 4.2. Frontend (FSD)

- `features/create-place/`, `features/update-place/` (мутации + UI-форма) — `useCreatePlaceMutation`, `useUpdatePlaceMutation`. Инвалидируют `placesQueryKeys.list()`.
- `features/toggle-place-activation/` — кнопка + `useTogglePlaceActivationMutation`. Оптимистическое обновление: `setQueryData` в `entities/place`.
- `features/delete-place/` — модалка подтверждения + мутация.
- `pages/place-form/` — обёртка одной страницы для create и edit (роутер: `/places/new` и `/places/:id/edit`).
- `shared/ui/Modal.tsx`, `shared/ui/Form.tsx` — переиспользуемые kvell-обёртки.

**Запреты в Фазе 3:**
- Бизнес-логика в `ui/`. Логика мутаций — в `api/`, валидация — в `lib/`, состояния модалок — в `model/`.

### 4.3. Тесты

- Feature: каждый mutation endpoint + проверка изоляции тенантов (чужую точку не редактируем).

---

## 5. Фаза 4 — Управление отзывами

### 5.1. Backend API

- `PATCH /api/owner/reviews/{id}/status` → `ChangeReviewStatusHandler` (с проверкой owner_id, уже есть).

### 5.2. Frontend (FSD)

- `features/change-review-status/api/useChangeReviewStatusMutation.ts` — `useMutation`. На onMutate — optimistic update в кеше `entities/review`.
- `features/change-review-status/ui/StatusSwitcher.tsx` — bottom-sheet на мобиле (через `shared/ui/BottomSheet`), dropdown на ≥sm.
- Использовать из `widgets/reviews-table` через public API features-слайса (`@/features/change-review-status`).

### 5.3. Тесты

- Feature: смена статуса своего отзыва / 403 на чужом / 422 на невалидном переходе.

---

## 6. Фаза 5 — Подписка и оплата

### 6.1. Backend API

- `GET /api/owner/subscription` → `{tariff, endsAt, daysLeft, isActive, placesUsed, placesLimit}`.
- `POST /api/owner/subscription/init-payment` → `InitSubscriptionPaymentHandler` (есть), возвращает `{paymentUrl}`.
- `GET /api/owner/payments?page=` → история транзакций owner'а.
  - Новый Reader `OwnerPaymentsReader`.

### 6.2. Frontend (FSD)

- `entities/subscription/api/useSubscriptionQuery.ts`, `entities/payment/api/useOwnerPaymentsQuery.ts`.
- `features/extend-subscription/api/useInitPaymentMutation.ts` — на onSuccess `window.location.href = data.paymentUrl`.
- `pages/subscription/ui/SubscriptionPage.tsx` — карточка статуса + history-таблица. Композит из `widgets/subscription-card` и `widgets/payments-history`.
- После возврата с `/payment/success` SPA инвалидирует `subscriptionQueryKeys.current()` и `sessionQueryKeys.me()`.

### 6.3. Тесты

- Feature: init payment возвращает url, история не видит чужие платежи.

---

## 7. Фаза 6 — Профиль и настройки

### 7.1. Backend API

- `PATCH /api/owner/profile` → `UpdateOwnerProfileHandler` (есть). Поля: name, email, subdomain.
- `POST /api/owner/profile/telegram/relink` → выдача нового кода для перепривязки.

### 7.2. Frontend (FSD)

- `features/update-profile/api/useUpdateProfileMutation.ts`.
- `features/relink-telegram/api/useRelinkTelegramMutation.ts`.
- `pages/profile/ui/ProfilePage.tsx` — секции: основные данные, Telegram-подключение, каналы уведомлений (toggles).
- Toast при сохранении через `sonner` (он же объявлен в `app/providers`).

### 7.3. Тесты

- Feature: смена email с валидацией уникальности, смена slug → должен освободить старый поддомен.

---

## 8. Фаза 7 — Полировка UX, PWA, edge

### 8.1. PWA

- Manifest, service worker (Workbox через vite-plugin-pwa).
- Runtime cache: `cache-first` для статики, `network-first` для `/api/owner/me` и `/api/owner/dashboard`.
- A2HS prompt: кастомный banner на 3-й заход.

### 8.2. UX-полировка

- Skeleton loaders на каждой List/Detail-странице.
- Empty states с иллюстрациями (SVG из undraw.co, перекрашенные в accent-цвет).
- Тосты успеха/ошибки через `sonner`.
- Анимации перехода между страницами через Framer Motion (опционально).
- Бесшовные модалки / bottom-sheets на мобиле.

### 8.3. Безопасность

- CSRF: Sanctum-cookie + `withCredentials: true` в axios.
- Rate-limit: `throttle:60,1` на `/api/owner/*`, `throttle:10,1` на login-exchange.
- Cross-tenant guard: middleware `EnsureSessionMatchesTenant` — если `auth('owner')->id() !== resolvedTenant.id`, разлогинить + 403.
- Subscription expired → 402 + редирект SPA на `/subscription`.

### 8.4. Тесты

- Feature: cross-tenant атака даёт 403.
- Feature: истёкшая подписка блокирует мутации (но не read).

---

## 9. Фаза 8 — CI, документация, релиз

- Backend: добавить тесты в `composer test`.
- Frontend: `vitest run` + `tsc --noEmit` + `eslint` в CI.
- Docker: multi-stage build `frontend/owner` → Caddy раздаёт статику или Laravel `public/owner-assets/`.
- Caddy reverse-proxy: SPA fallback `try_files /owner/index.html`.
- Документация: `backend/docs/owner-panel.md` — финальный гайд (как добавить страницу/endpoint, как привязать к существующему use case).

---

## 10. Открытые вопросы (решить по ходу)

- **Slug-смена в Profile**: меняем `subdomain_slug` → пользователь теряет текущую сессию (cookie привязана к старому поддомену). UX: показать предупреждение «вам придётся перелогиниться».
- **MAX-мессенджер**: канал уведомлений отключён (`MAX_BOT_ENABLED=false`). UI рисуем «coming soon» или просто скрываем.
- **Импорт/экспорт отзывов** — out of scope v1, добавить в backlog.
- **Уведомления в реальном времени** (новый негативный отзыв) — Pusher / Reverb? Out of scope v1, polling `staleTime: 30s` достаточно.
- **Перевод на другие языки** — UI инлайнится по-русски; i18n-key инфраструктуру можно добавить позже без переписывания.

---

## 11. Cheatsheet (по аналогии с админ-планом)

### Команды

```bash
# Frontend dev
cd frontend/owner && npm i && npm run dev

# Frontend build
cd frontend/owner && npm run build

# Backend тесты (в Docker, PHP 8.4)
docker run --rm -v $(pwd)/backend:/app -w /app php:8.4-cli-alpine sh -c \
  "apk add --no-cache icu-libs icu-dev libzip-dev zip > /dev/null 2>&1 && \
   docker-php-ext-install intl > /dev/null 2>&1 && \
   php artisan test tests/Feature/Owner tests/Unit/Application/Iam"

# Frontend тесты
cd frontend/owner && npm run test
```

### Эталоны

| Если делаешь...                | Смотри...                                                            |
|--------------------------------|----------------------------------------------------------------------|
| API endpoint                   | `app/Interface/Http/Controllers/Public/SubmitReviewController.php`   |
| FormRequest                    | `app/Interface/Http/Requests/Public/SubmitReviewRequest.php`         |
| Sanctum-auth flow              | TBD — будет создан в Фазе 1                                          |
| Reader для list-страницы       | `app/Application/Reviews/ListRecentReviewsForOwner/`                 |
| Tenant middleware              | `app/Http/Middleware/ResolveTenantBySubdomain.php`                   |
| React Query hook (query)       | `entities/<x>/api/useXxxQuery.ts` (после Фазы 0.5)                   |
| React Query hook (mutation)    | `features/<x>/api/useXxxMutation.ts`                                 |
| Query-keys (тип-безопасные)    | `entities/<x>/config/queryKeys.ts`                                   |
| Public API слайса              | `<layer>/<slice>/index.ts`                                           |
| HTTP-клиент                    | `shared/api/httpClient.ts` (только внутри hooks из api/-сегментов)   |
| Tailwind токены                | `frontend/owner/tailwind.config.ts` (Фаза 0)                         |
| UI-компоненты с kvell-стилем   | `shared/ui/` (Button, Card, Input, Badge — после Фазы 0.5)           |

---

## 12. Чек-лист «добавить новую страницу/фичу в Owner-панели»

### Backend (DDD-цепочка)

1. **Domain.** Если нужны новые мутации — методы в агрегат + Repository.
2. **Application.** Папка `<UseCase>/` (Command/Query + Handler). Чаще — переиспользовать существующие.
3. **Infrastructure.** Если новый Reader — реализация + биндинг.
4. **Interface HTTP:**
   - `routes/api.php` — добавить в группу `prefix('owner')->middleware(['tenant','auth:owner','tenant-owns-session'])`.
   - `FormRequest` с `toCommand()`.
   - Контроллер 5–7 строк: command → handler → response.

### Frontend (FSD-цепочка)

**Сверху вниз — добавляй слой, только если его ещё нет:**

5. **`entities/<resource>/`** (если работаешь с новой бизнес-сущностью):
   - `model/types.ts` — типы DTO (branded ID, если уместно).
   - `api/use<Resource>Query.ts` + `api/use<Resource>Mutation.ts` — React Query hooks.
   - `config/queryKeys.ts` — фабрика query-keys.
   - `ui/<Resource>Card.tsx` — визуальные примитивы (если они переиспользуются между фичами).
   - `index.ts` — public API.
6. **`features/<verb-noun>/`** (для действий: `toggle-place-activation`, `change-review-status`):
   - `api/use<Action>Mutation.ts` — мутация + invalidate/optimistic update.
   - `lib/` — pure-функции (валидация, форматтеры).
   - `model/` — Zustand-store для UI-состояния этой фичи (если нужно).
   - `ui/<Action>Button.tsx` — кнопка/форма/модалка.
   - `index.ts`.
7. **`widgets/<composite>/`** — если несколько фичей+entities складываются в переиспользуемый блок (например, `reviews-table`).
8. **`pages/<route>/`** — тонкая страница, собирает widgets + features + entities. `ui/<Route>Page.tsx` + `index.ts`.
9. **`app/router/`** — добавить маршрут на новую страницу.

### Правила, которые ESLint обязан проверять

- ❌ Прямой `axios.get('/api/owner/...')` в `ui/` любого слайса.
- ❌ `import { foo } from '@/entities/place/api/useFooQuery'` — только `import { useFooQuery } from '@/entities/place'`.
- ❌ `class FooStore { ... }` — Zustand-store пишем функцией `create((set) => ({...}))`.
- ❌ Импорт «вверх» (например, `entities/place` → `features/...`).
- ❌ Кросс-слайс импорт без public API (`entities/place` → `entities/review` напрямую).

### Завершение

10. **Тесты:**
    - Backend Feature на endpoint (happy + auth + cross-tenant 403).
    - Frontend RTL на главный сценарий страницы. Mock React Query через `QueryClient` в тесте.
11. **Lint + типы:** `npm run lint && npm run typecheck` в `frontend/owner/`. Pint на backend.
12. **Док:** обновить `owner-panel-plan.md` (если меняется API-контракт или появляется новый слайс/widget).
