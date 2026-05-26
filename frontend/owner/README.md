# Owner Panel (React SPA)

Личный кабинет владельца заведения. Монтируется на `{slug}.otziv.space/owner`.
Полный план — `backend/docs/owner-panel-plan.md`.

## Стек

- React 18 + Vite + TypeScript
- Tailwind CSS (Kvell-токены в `tailwind.config.ts`)
- TanStack Query (server state) + Zustand (UI state)
- axios + Sanctum SPA cookie-auth (`shared/api/httpClient`, ходить **только через React Query hooks**)
- React Router 6
- vite-plugin-pwa
- vitest + React Testing Library

## Архитектура — Feature-Sliced Design (строго)

Канон: https://feature-sliced.design/. Применяется ко всему `src/`.

### Слои (импорт строго ВНИЗ)

```
src/
├── app/        # composition root: providers, router, styles
├── pages/      # одна страница ≈ один маршрут
├── widgets/    # композитные блоки UI (AppShell, ReviewsTable)
├── features/   # пользовательские действия (toggle-place-activation, change-review-status)
├── entities/   # бизнес-сущности (place, review, owner, session, subscription)
└── shared/     # ui-kit, lib, api-клиент, config, типы
```

- `app/` импортирует всё; `pages/` — из `widgets/features/entities/shared`;
  `widgets/` — из `features/entities/shared`; `features/` — из `entities/shared`;
  `entities/` — только из `shared`; `shared/` — ни от чего.
- **Внутри слоя слайсы НЕ импортируют друг друга** — только через public API
  (`<layer>/<slice>/index.ts`).
- ESLint (`eslint-plugin-boundaries`) проверяет это автоматически —
  настройка добавится в Фазе 0.5.

### Сегменты слайса

```
slice/
├── ui/         # React-компоненты (FP, hooks)
├── model/      # zustand-store, useXxx-хуки бизнес-состояния
├── api/        # React Query hooks: useXxxQuery, useXxxMutation
├── lib/        # pure helpers (форматтеры, валидаторы)
├── config/     # константы, query-keys
└── index.ts    # ПУБЛИЧНОЕ API. Импорт извне — только через index.
```

## Правила, которые мы не нарушаем

1. **Только функциональное программирование.**
   - Никаких `class Foo {}` в `src/`. Все state-managers — функции/замыкания
     (Zustand, кастомные hooks). Сервисные функции — pure где возможно;
     побочные эффекты изолированы в `<slice>/lib/` или `shared/lib/`.
2. **Server state — только TanStack Query.**
   - Прямые `fetch` / `axios.get` в `ui/`, `pages/`, `widgets/` — запрещены.
   - Любой сетевой вызов оборачивается в `useXxxQuery` или `useXxxMutation`
     и живёт в `<slice>/api/`.
   - HTTP-клиент один — `shared/api/httpClient` (axios + `withCredentials` + `ensureCsrf`).
     Наружу из `api/`-сегмента экспортируется только React Query hook.
3. **Query-keys — фабрика в `<slice>/config/queryKeys.ts`.**
   - Один источник правды на инвалидацию.
4. **Public API через `index.ts`.**
   - Deep-import'ы (`@/entities/place/ui/PlaceCard`) запрещены ESLint'ом.

## Команды (из `frontend/`)

```bash
npm run dev:owner       # Vite на :5174, proxy /api и /sanctum → :8000
npm run build:owner     # → ../../dist/owner/index.html
npm run test --workspace owner
```

## Расположение собранного бандла

`dist/owner/index.html` (от корня репозитория). Этот файл читает
`OwnerSpaController` — он же отдаёт его на любой подпуть `/owner/*`.

## ⚠️ Текущее состояние (Фаза 0)

**Каркас заскаффолжен функционально, но структурно НЕ соответствует FSD.**
Полный план миграции — `backend/docs/owner-panel-plan.md`, секция «1.6 — Что
нужно исправить в текущем каркасе». Кратко:

| Сейчас                                       | Цель (Фаза 0.5)                                       |
|----------------------------------------------|--------------------------------------------------------|
| `src/main.tsx`                               | `src/app/index.tsx`                                   |
| `src/App.tsx`                                | `src/app/router/` + `src/app/providers/`              |
| `src/styles/`                                | `src/app/styles/`                                     |
| `src/layouts/AppShell.tsx`                   | `src/widgets/app-shell/`                              |
| `src/layouts/AuthLayout.tsx`                 | `src/widgets/auth-shell/`                             |
| `src/features/dashboard/DashboardPage.tsx`   | `src/pages/dashboard/`                                |
| `src/features/auth/LoginPage.tsx`            | `src/pages/login/`                                    |
| `src/features/system/*.tsx`                  | `src/pages/not-found/`, `src/pages/placeholder/`      |
| `src/api/client.ts`                          | `src/shared/api/httpClient.ts`                        |
| `src/lib/auth.ts`                            | `src/entities/session/`                               |

Плюс в Фазе 0.5 нужно подключить:
- `eslint-plugin-boundaries` (FSD-границы) + правила `no-restricted-syntax`
  для запрета `class` и `no-restricted-imports` для запрета прямых `fetch`/`axios`
  вне `shared/api/` и `<slice>/api/`.
- Алиасы `@/app`, `@/pages`, `@/widgets`, `@/features`, `@/entities`, `@/shared`
  в `tsconfig.json` и `vite.config.ts`.
- Перенос `@layer components` (.card-padded, .btn-primary) в `shared/ui/`
  как React-компоненты (`<Card>`, `<Button variant="primary">`).

Текущие смоки (`src/App.test.tsx`) после миграции переезжают в
`pages/<name>/ui/<Name>Page.test.tsx`.

## Стилевые правила (Kvell Merchant)

- Фон: `bg-canvas` (`#FAFAF7`).
- Карточки: после Фазы 0.5 — `<Card>` из `shared/ui` (вместо .card-padded).
- Кнопки: `<Button variant="primary" | "ghost">`.
- Accent: `accent` (#10B981), `danger` (#F97066), `warning` (#F59E0B).
- Mobile-first: 360–414px база, sm/md/lg breakpoints.
- На мобиле — bottom-nav. На ≥lg — sidebar. Сейчас лежит в `layouts/` —
  после миграции переедет в `widgets/app-shell/`.
