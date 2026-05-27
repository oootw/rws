# Design System + Feature Flags по тарифам — план для холодной сессии

Документ-handoff. Цель — следующая сессия с **холодным контекстом** должна
выполнить план, не перечитывая остальную историю проекта.

---

## ⏱ Текущий статус

| Фаза | Статус | Кратко |
|------|--------|--------|
| A1   | ✅ сделано | Shared дизайн-токены: `frontend/shared/{design-tokens.ts, tailwind/preset.ts, styles/tokens.css}` + sync-тест |
| A2   | ✅ сделано | Tailwind в `frontend/scan/` через тот же preset; `main.css` переписан на `@layer components` |
| A3   | ✅ сделано | React-примитивы в `frontend/shared/ui/`: 7 перенесённых (Button/Card/Input/Skeleton/EmptyState/ConfirmDialog/Sparkline) + 6 новых (Stack/Field/Textarea/Select/Badge/Spinner); shim `owner/src/shared/ui/index.ts` re-export'ит из shared, ESLint запрещает deep-импорты, CI обновлён |
| A4   | ✅ сделано | `scan/render.ts` перешёл на utility-классы через константы в `scan/styles.ts`; `main.css` оставил только `@layer base` (CSS 10.27 → 12.04 KB — приемлемо) |
| A5   | ✅ сделано | Применили `<Field>` в ProfileForm/PlaceForm/CodeForm, `<Badge>` в ReviewCard/PaymentsHistory, `<Select>` в PlaceForm, `<Spinner>` в кнопках с `isPending`. CSS 19.16 → 18.97 KB |
| B1   | ✅ сделано | `Feature` enum (9 cases + label()); `Tariff` расширен `features: list<Feature>` + `hasFeature()`; `TariffMapper::mapFeatures()` глотает legacy assoc/null/unknown |
| B2   | ✅ сделано | `GetOwnerFeatures{Query,Handler}` — резолвит tariff (bound → default → []) и возвращает `list<Feature>` |
| B3   | ✅ сделано | `ApiErrorCode::FeatureNotAvailable`, `RequireFeature` middleware (alias `feature:`), `OwnerFeaturesController` + `GET /api/owner/features`, `OwnerFeatureGuardTest` |
| B4   | ✅ сделано | `TariffForm`: `CheckboxList::make('features')` с options из `Feature::cases()`. `TariffInfolist`: `TextEntry` со списком переведённых лейблов |
| B5   | ✅ сделано | `TariffSeeder` пишет `[Feature::MultiplePlaces->value]` + `extra_place_price` ушёл в свою колонку; `TariffFactory.features = []`; миграция `2026_05_27_120000_normalize_tariff_features.php` нормализует legacy в БД |
| B6   | ✅ сделано | `frontend/owner/src/entities/features/`: types, queryKeys, useFeaturesQuery (staleTime 5min), useFeature, FeatureGate, UpsellCard. 50/50 vitest зелёные |
| B7   | ✅ сделано | `feature:multiple_places` на `POST /api/owner/places` (после `subscription.active:402`); `<FeatureGate>` на кнопку «Добавить точку» в `PlacesListPage` и на `/places/new` (UpsellCard); `RequireSession` параллельно прогревает `useFeaturesQuery` и чистит кэш на логауте |
| B8   | ✅ сделано | `PublicPlaceView` расширен `tariffFeatures: list<string>` (whitelist: только `custom_branding`/`qr_themes`); `GET /api/public/places/{place}` отдаёт `tariff_features`; shared `ScanFeature` тип; scan-сторона — заглушка, без реального брендинга |

**Артефакты на диске после A1+A2:**
- `frontend/shared/design-tokens.ts` — типизированные константы (`colors`, `radii`, `shadows`, `motion`, `typography`, `sizes`) + `tokens` + тип `DesignTokens`.
- `frontend/shared/tailwind/preset.ts` — Tailwind preset (без `content`).
- `frontend/shared/styles/tokens.css` — те же значения как CSS variables.
- `frontend/shared/package.json` — `exports`: `./design-tokens`, `./tailwind`, `./styles/tokens.css`, `./types`.
- `frontend/owner/tailwind.config.ts` — упрощён до `presets: [preset]`.
- `frontend/owner/postcss.config.js` — добавлен `postcss-import`.
- `frontend/owner/src/app/styles/index.css` — импорт shared tokens.css.
- `frontend/owner/src/shared/design-tokens.test.ts` — sync-тест tokens.ts ↔ tokens.css.
- `frontend/owner/tsconfig.json` — `types: [..., "node"]` (для fs в sync-тесте).
- `frontend/scan/{tailwind,postcss}.config.{ts,js}` — новые.
- `frontend/scan/src/styles/main.css` — переписан, **`render.ts` не менялся** (контракт CSS-классов сохранён).
- `.github/workflows/ci.yml` — job `frontend-scan` (typecheck + build).

**Проверки зелёные:**
- `npm run build:owner` (CSS 18.47 KB) / `build:scan` (CSS 10.27 KB).
- `npm run typecheck/lint/test --workspace owner` (33/33).
- `npm run typecheck --workspace scan`.

**Лессоны A1+A2 (читать перед A3):**
1. **`postcss-import`** обязателен, если consumer'у нужно `@import '@guard-reviews/shared/styles/tokens.css'`. Без него Tailwind не разворачивает шаги.
2. **Workspace-импорты** (`@guard-reviews/shared/...`) работают через `exports` в `frontend/shared/package.json`. **Не** через TypeScript paths — через настоящие subpath exports. Это значит и vite, и tsc, и vitest, и postcss-import резолвят без доп. настройки.
3. **Tailwind preset наследует только `theme`** — не `content`. Каждый consumer задаёт свои глобы.
4. **`render.ts` в scan генерирует HTML-строки** — Tailwind должен сканировать `.ts` тоже (`content: ['./src/**/*.{ts,html}']`).
5. **Warning «No utility classes were detected»** в scan-build — косметика: scan использует только `@apply` внутри `@layer components`. Можно игнорировать; если надо убрать — добавить utility-классы прямо в `render.ts`.
6. **Sync-тест tokens.ts ↔ tokens.css** требует `@types/node` в owner-workspace (для `node:fs`/`node:path`). Tsconfig `types: ["...", "node"]`.
7. **Визуальная регрессия в scan:** primary-button был `#111` чёрный → стал `bg-accent` зелёный. Намеренный переход на бренд. Если кто-то увидит — это OK.
8. **Cast `preset as Config`** в `presets: [preset as Config]` нужен — мы экспортируем `Pick<Config, 'theme'>` (без `content`), а `presets` принимает `Partial<Config>`. TypeScript строгий, без cast не пройдёт.

---

Связанные документы (читать сначала, в этом порядке):
1. `backend/саммари.md` — общая DDD-карта проекта (5 минут).
2. `backend/docs/owner-panel.md` — финальный гайд по Owner-панели (10 минут).
3. `backend/docs/architecture.md` — правила слоёв.

Не нужно читать: `backend/docs/owner-panel-plan.md` (исторический лог),
`backend/админка-план.md` (исторический лог Filament-админки).

---

## 0. TL;DR — что делаем и почему

**Две независимые, но связанные задачи. Можно делать последовательно
(рекомендуется) или параллельно разными PR'ами.**

### Задача A. Дизайн-система для `scan` и `owner`

**Проблема сегодня:**
- `frontend/owner/` имеет свои `shared/ui/` (Button, Card, Input, …) и Tailwind-токены в `tailwind.config.ts`.
- `frontend/scan/` — vanilla TypeScript (без React), свой `src/styles/main.css`, дизайн «отдельная вселенная».
- Токены дублируются / расходятся, любое изменение бренда требует двух правок.

**Цель:** одна **единая точка правды для бренда** (цвета, типографика, тени, радиусы, motion) +
переиспользуемые UI-примитивы для React-проектов (owner), и CSS-классы-«рецепты»
для vanilla-проектов (scan). Всё через **Tailwind preset + CSS custom properties**.

Не делаем громоздкий monorepo с публичной библиотекой — KISS: всё внутри
существующего workspace `@guard-reviews/shared`.

### Задача B. Feature-flags по тарифам

**Проблема сегодня:**
- В `tariffs.features` (JSON-колонка) уже хранится «что-то» как key-value
  (сейчас только `extra_place_price`), но эта структура **не типизирована**,
  никем не проверяется, в Filament — `KeyValue` без enum.
- Бекенд не умеет ответить на вопрос «у этого owner'а есть фича X?».
- Frontend не умеет скрывать/блокировать UI на основе тарифа.

**Цель:** типизированный enum `Feature` в Domain, контракт чтения
«какие фичи доступны owner'у», middleware/decorator для блокировки
endpoint'ов, и frontend-примитивы `useFeature(flag)` + `<FeatureGate>`.

Расширение «добавить фичу» = 1 case в enum + 1 чекбокс в Filament + UI-gate
в нужном месте. **Никаких миграций при добавлении новой фичи.**

---

## 1. Архитектурные решения (фиксированы — не пересматривать)

### A. Дизайн-система

1. **Источник истины токенов** — `frontend/shared/design-tokens.ts`
   (экспортирует объект констант: `colors`, `radii`, `shadows`, `motion`,
   `typography`, `spacing`).
2. **Tailwind preset** — `frontend/shared/tailwind/preset.ts` строится
   из `design-tokens.ts`. Оба `frontend/owner/tailwind.config.ts` и
   `frontend/scan/tailwind.config.ts` его наследуют через `presets: [preset]`.
   **`scan` сейчас без Tailwind** — добавим (это часть плана, см. Фаза A2).
3. **CSS custom properties** генерируются из тех же токенов и инжектятся
   через `frontend/shared/styles/tokens.css`. Это позволяет (а) использовать
   токены без Tailwind в vanilla-CSS, (б) рантайм-темизацию в будущем.
4. **React-компоненты-примитивы** живут в `frontend/shared/ui/` (TS-исходники,
   собираются и тайпчекаются вместе с консьюмером — это monorepo-workspace,
   не пакет в npm; импорт `@guard-reviews/shared/ui`).
5. **Никаких сторонних UI-китов** (Radix/shadcn/MUI). Свои тонкие FP-only
   компоненты на Tailwind — для контроля бандл-сайза и брендинга.
6. **FP-only** для React-компонентов (никаких классов — это уже правило
   проекта, см. ESLint в `frontend/owner/`).
7. **Доступность важна:** все интерактивные примитивы — с `aria-*`,
   focus-visible-стилем, поддержкой клавиатуры.
8. **Не делаем для scan**: модальные React-компоненты. Scan получает
   только CSS-классы и helper-функции (например, `applyKvellSurface(element)`).
   Это держит scan-бандл лёгким.

### B. Feature flags

1. **Источник истины** — PHP enum `App\Domain\Iam\Feature` (строковые
   backed-values; стабильные ключи — менять нельзя, только депрекейтить).
2. **Хранение** — существующая JSON-колонка `tariffs.features` →
   список строк (`["multiple_places", "negative_alerts_telegram", ...]`).
   **Миграция данных не нужна** — старые ключи (`extra_place_price`) сначала
   терпим как legacy, потом удаляем (см. Фаза B5).
3. **Tariff VO** расширяется `features: array<Feature>`. Mapper маппит
   JSON-список в типизированный массив, неизвестные ключи игнорируются
   с warning-логом (forward-compat).
4. **Use case `GetOwnerFeatures(ownerId): array<Feature>`** — единственный
   способ узнать что доступно. Учитывает default-тариф fallback.
5. **HTTP middleware `feature:<key>`** проверяет фичу против текущего
   owner'а. 403 + `ApiErrorCode::FeatureNotAvailable` при отсутствии.
6. **Frontend hook `useFeature(flag): boolean`** через React Query —
   читает `GET /api/owner/features`, кэшируется ≥5 минут.
7. **Frontend компонент `<FeatureGate feature="...">`** — рендерит детей
   или fallback (`<UpsellCard>`).
8. **Subscription guard и Feature guard ортогональны.** Subscription
   проверяет «оплачено ли», Feature — «положена ли по тарифу». Оба
   могут срабатывать одновременно. UX-приоритет: 402 > feature-403.
9. **Filament** заменяет `KeyValue` на `CheckboxList::make('features')`
   с options из enum.
10. **Бэкенд — авторитет.** Frontend hook — только UX-подсказка; реальное
    отрезание — на API-уровне через middleware.

---

## 2. Список фич (стартовый набор)

Для понимания: с какими ключами enum заводится. **Этот список НЕ финальный
и согласуется на старте Фазы B1.** Прикинут на основе текущей кодовой базы
(см. `OwnerSpaController`, `OwnerPlacesController`, `Notifications/`).

Кандидаты:
- `multiple_places` — больше 1 точки (иначе `places_limit=1` тариф).
- `weekly_digest` — еженедельный дайджест (`SendWeeklyDigest` job).
- `negative_alerts_telegram` — мгновенные уведомления о негативе в Telegram.
- `negative_alerts_email` — то же в email (fallback-канал).
- `custom_branding` — логотип/цвета на странице сканирования.
- `qr_themes` — выбор оформления QR.
- `csv_export_reviews` — экспорт отзывов в CSV (выпуск из админки или из ЛК).
- `api_access` — будущий публичный API для интеграций.
- `priority_support` — формальная фича; влияет только на UI.

**Уточнить с продуктом на старте сессии.** Если список меняется —
плюс/минус cases в enum. Главное — enum, а не сами имена.

---

## 3. Фазы (порядок выполнения)

Рекомендуется идти **A1→A2→A3→A4 → B1→B2→B3→B4 → A5 (применение)**.

### Фаза A1. Токены + Tailwind preset (фундамент) — ✅ СДЕЛАНО

> Артефакты — см. блок «Текущий статус» в начале документа. Секция оставлена
> как историческая справка о решениях. **Не переделывать.**


**Файлы:**
- `frontend/shared/design-tokens.ts` — экспорт констант, типизированный:
  ```ts
  export const colors = {
    canvas: '#FAFAF7',
    surface: '#FFFFFF',
    ink: { 900: '#0F172A', 700: '#334155', 500: '#64748B', 400: '#94A3B8', 200: '#E2E8F0', 100: '#F1F5F9' },
    accent: { DEFAULT: '#10B981', fg: '#FFFFFF', soft: '#ECFDF5' },
    danger: { DEFAULT: '#F97066', fg: '#FFFFFF', soft: '#FEF2F2' },
    warning: { DEFAULT: '#F59E0B', fg: '#1F2937', soft: '#FFFBEB' },
    success: { DEFAULT: '#10B981', fg: '#FFFFFF', soft: '#ECFDF5' },
  } as const;
  export const radii = { md: '0.75rem', xl: '1rem', '2xl': '1rem', '3xl': '1.5rem', full: '9999px' } as const;
  export const shadows = {
    soft: '0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.04)',
    lift: '0 4px 12px rgba(15, 23, 42, 0.06), 0 12px 36px rgba(15, 23, 42, 0.08)',
  } as const;
  export const motion = {
    fast: '120ms cubic-bezier(0.4, 0, 0.2, 1)',
    base: '200ms cubic-bezier(0.4, 0, 0.2, 1)',
    slow: '320ms cubic-bezier(0.4, 0, 0.2, 1)',
  } as const;
  export const typography = {
    fontSans: ['Inter', 'Manrope', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
    fontMono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
  } as const;
  ```
- `frontend/shared/tailwind/preset.ts` — превращает константы в Tailwind config:
  ```ts
  import type { Config } from 'tailwindcss';
  import { colors, radii, shadows, motion, typography } from '../design-tokens';
  export const preset: Pick<Config, 'theme'> = {
    theme: { extend: { colors, borderRadius: radii, boxShadow: shadows,
      fontFamily: { sans: [...typography.fontSans], mono: [...typography.fontMono] },
      transitionDuration: { base: '200ms', fast: '120ms', slow: '320ms' },
    } },
  };
  ```
- `frontend/shared/styles/tokens.css` — CSS variables:
  ```css
  :root {
    --color-canvas: #FAFAF7;
    --color-surface: #FFFFFF;
    --color-ink-900: #0F172A;
    /* … все цвета из tokens.ts */
    --shadow-soft: 0 1px 3px rgba(15,23,42,.04), 0 8px 24px rgba(15,23,42,.04);
    --radius-2xl: 1rem;
    --motion-base: 200ms cubic-bezier(.4,0,.2,1);
  }
  ```
  Генерация **руками синхронно с tokens.ts** (мелкий объём, не стоит
  кодогена). Зафиксировать unit-тест/lint, что список ключей соответствует.

- `frontend/shared/package.json` — добавить exports:
  ```json
  {
    "exports": {
      "./types": "./types/api.ts",
      "./design-tokens": "./design-tokens.ts",
      "./tailwind": "./tailwind/preset.ts",
      "./styles/tokens.css": "./styles/tokens.css"
    }
  }
  ```
- `frontend/owner/tailwind.config.ts` — переключить на `presets: [preset]`,
  удалить дублирующиеся `colors/shadows/radii/fontFamily`.
- `frontend/owner/src/app/styles/index.css` — `@import '@guard-reviews/shared/styles/tokens.css';`
  (или через postcss-import).

**Definition of done:**
- `npm run typecheck/lint/test/build --workspace owner` зелёные.
- Скриншот dashboard'а до/после идентичен (визуально).

### Фаза A2. Tailwind в `frontend/scan/` — ✅ СДЕЛАНО

> Артефакты — см. блок «Текущий статус» в начале документа. Секция оставлена
> как историческая справка о решениях. **Не переделывать.**


Сейчас `scan/` — vanilla TS без Tailwind, плоский `main.css`.

**Шаги:**
1. Добавить в `frontend/scan/package.json` devDeps: `tailwindcss`, `postcss`,
   `autoprefixer`, `@guard-reviews/shared` (последний — уже есть как dep).
2. `frontend/scan/tailwind.config.ts`:
   ```ts
   import { preset } from '@guard-reviews/shared/tailwind';
   export default {
     content: ['./index.html', './src/**/*.{ts,html}'],
     presets: [preset],
   };
   ```
3. `frontend/scan/postcss.config.js`.
4. `frontend/scan/src/styles/main.css` — переписать через `@tailwind base/components/utilities`
   + импорт `@guard-reviews/shared/styles/tokens.css` + слой `@layer components`
   для классов-рецептов (`.surface-card`, `.btn-primary`, `.input-base`, …).
5. Проверить vite.config: tailwind подхватится через postcss автоматически.

**Definition of done:**
- `npm run build --workspace scan` зелёный.
- Открыть scan-страницу локально, проверить — рендер не сломан.
- Скриншот «до/после» — допустимо небольшое визуальное расхождение
  (это и есть переход на брендовые токены).

### Фаза A3. React-примитивы в `frontend/shared/ui/`

**Цель:** одна точка правды для React UI-примитивов, потребляется через
`@guard-reviews/shared/ui`. Owner перестаёт держать `src/shared/ui/`; scan не
получает React-компонентов (он vanilla).

#### A3.0 — Setup workspace под React-компоненты

`frontend/shared/` сейчас тип `"type": "module"` + только types/css/ts-exports.
Чтобы консьюмеры могли импортировать TSX (`Button`, …):

1. **Добавить React как peerDep** в `frontend/shared/package.json`:
   ```json
   {
     "peerDependencies": { "react": "^18.3.1" },
     "devDependencies": { "@types/react": "^18.3.12", "react": "^18.3.1" }
   }
   ```
   peer — чтобы owner не таскал второй React; dev — чтобы tsc локально
   видел типы.
2. **Расширить `exports`** в `frontend/shared/package.json`:
   ```json
   {
     "exports": {
       "./types": "./types/api.ts",
       "./design-tokens": "./design-tokens.ts",
       "./tailwind": "./tailwind/preset.ts",
       "./styles/tokens.css": "./styles/tokens.css",
       "./ui": "./ui/index.ts"
     }
   }
   ```
3. **Создать `frontend/shared/tsconfig.json`** (его сейчас нет):
   ```json
   {
     "compilerOptions": {
       "target": "ES2022",
       "module": "ESNext",
       "moduleResolution": "bundler",
       "jsx": "react-jsx",
       "strict": true,
       "noEmit": true,
       "skipLibCheck": true,
       "allowImportingTsExtensions": true,
       "verbatimModuleSyntax": true,
       "lib": ["ES2022", "DOM", "DOM.Iterable"]
     },
     "include": ["ui", "design-tokens.ts", "tailwind", "types"]
   }
   ```
4. **Скрипты** в `frontend/shared/package.json`:
   ```json
   "scripts": { "typecheck": "tsc --noEmit" }
   ```
   Это подцепится `npm run typecheck --workspaces --if-present`.
5. **Tailwind должен сканировать shared/ui:** добавить в `content`-globe owner'а:
   ```ts
   // frontend/owner/tailwind.config.ts
   content: [
     './index.html',
     './src/**/*.{ts,tsx}',
     '../shared/ui/**/*.{ts,tsx}',  // ← добавить
   ],
   ```
   (Если этого не сделать, Tailwind не вытащит классы из shared-компонентов и
   они отрисуются без стилей.)
6. **CI:** обновить `.github/workflows/ci.yml` — добавить step
   `npm run typecheck --workspace shared` в `frontend-owner` job (или
   отдельный `frontend-shared`).

#### A3.1 — Перенос существующих (atomic commit)

Перенести **один-в-один** без правок API:

| Из owner                                          | В shared                              |
|---------------------------------------------------|---------------------------------------|
| `src/shared/ui/Button.tsx`                        | `ui/Button.tsx`                       |
| `src/shared/ui/Card.tsx`                          | `ui/Card.tsx`                         |
| `src/shared/ui/Input.tsx`                         | `ui/Input.tsx`                        |
| `src/shared/ui/Skeleton.tsx`                      | `ui/Skeleton.tsx`                     |
| `src/shared/ui/EmptyState.tsx`                    | `ui/EmptyState.tsx`                   |
| `src/shared/ui/ConfirmDialog.tsx`                 | `ui/ConfirmDialog.tsx`                |
| `src/shared/ui/Sparkline.tsx`                     | `ui/Sparkline.tsx`                    |

Создать `frontend/shared/ui/index.ts`:
```ts
export { Button } from './Button';
export { Card } from './Card';
export { Input } from './Input';
export { Skeleton, SkeletonText } from './Skeleton';
export { EmptyState } from './EmptyState';
export { ConfirmDialog } from './ConfirmDialog';
export { Sparkline } from './Sparkline';
```

В owner-side — оставить **shim** `src/shared/ui/index.ts`:
```ts
// Backwards-compat: всё переехало в @guard-reviews/shared/ui.
// Этот файл удалим, когда обновим импорты по всему src/.
export * from '@guard-reviews/shared/ui';
```
Это **не задерживает** PR — импорты `import { ... } from '@/shared/ui'` будут
работать. В отдельном последующем коммите массово заменить на
`from '@guard-reviews/shared/ui'`, удалить shim.

**Тест после A3.1:** `npm run test/lint/typecheck/build --workspace owner` —
33/33 зелёные без правок.

#### A3.2 — Новые примитивы (по приоритету)

Добавлять можно по 2–3 за коммит. Приоритет (от высокого):

**Высокий — устраняет копипасту прямо сейчас:**
1. `Stack` (`direction: 'row'|'col'`, `gap: 1|2|3|4|6|8`, `align`, `justify`).
   Заменяет `space-y-*`/`flex gap-*` строки во всех страницах.
2. `Field` (`label`, `htmlFor`, `error?`, `hint?`, `children`).
   Заменяет тройку `<label>+<input>+<p.text-danger>` в `ProfileForm`,
   `PlaceForm`, `LoginPage`.
3. `Textarea` — нет сейчас, нужен для `delete-place reason`, `PlaceForm`.
4. `Select` — нативный `<select>` с брендовыми стилями.
5. `Badge` (`tone: 'neutral'|'accent'|'danger'|'warning'|'success'`).
   Заменяет `statusToneClass` в `ReviewCard`/`PaymentsHistory`.
6. `Spinner` — для `mutation.isPending` в кнопках вместо текста «Сохраняем…».

**Средний — UX-уплотнение:**
7. `Switch`, `Checkbox`, `Radio`, `RadioGroup` (нативные `<input>` + стили).
8. `Alert` / `Banner` — для top-of-page уведомлений.
9. `Tooltip` — лёгкая обёртка над `aria-describedby` + popover.
10. `Avatar` (initials + image-fallback).
11. `StatTile` — унифицирует `KpiCards` тайлы.

**Низкий — disclosure-семейство (можно отложить до B7 если нет нужды):**
12. `Modal` — через нативный `<dialog>` + `useDialog`-хук.
13. `BottomSheet` — мобильная версия Modal (slide-up).
14. `Tabs` (controlled).
15. `Pagination` — поднять из `widgets/reviews-table/`.

#### A3.3 — Тесты

**Шаблон теста:**
```tsx
// frontend/shared/ui/__tests__/<Component>.test.tsx
import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Field } from '../Field';

describe('Field', () => {
  it('связывает label с input через htmlFor/id', () => {
    render(<Field label="Email" htmlFor="email"><input id="email" /></Field>);
    const label = screen.getByText('Email');
    expect(label.getAttribute('for')).toBe('email');
  });

  it('показывает error с role=alert', () => {
    render(<Field label="X" htmlFor="x" error="неверно"><input id="x" /></Field>);
    expect(screen.getByRole('alert')).toHaveTextContent('неверно');
  });
});
```

**Где живут тесты:** в owner-workspace (`frontend/owner/src/shared/ui/__tests__/`)
ИЛИ можно завести vitest в shared-workspace. Прагматичнее — в owner: там уже
настроен jsdom + RTL. Тесты импортируют `from '@guard-reviews/shared/ui'`.

**Минимум:** на каждый новый компонент 1 тест (render + ARIA + минимум 1
интеракция). Старые тесты (Skeleton/AppRouter/SubscriptionCard/...) не трогать.

#### A3.4 — ESLint boundaries

`frontend/owner/eslint.config.js` уже запрещает кросс-слайс импорты и классы.
**Не нужно** добавлять правило про shared — он внешний workspace, импорты
через `@guard-reviews/...` уже разрешены.

**Но добавить deep-import запрет:**
```js
{
  rules: {
    'no-restricted-imports': ['error', {
      patterns: [{
        group: ['@guard-reviews/shared/ui/*'],
        message: 'Import from @guard-reviews/shared/ui (no deep paths).',
      }],
    }],
  },
}
```

#### A3.5 — Подводные камни A3 (предотвратить заранее)

1. **Tailwind не видит классы в shared/ui** → пустые стили. Лечится
   `content` globe в owner-config (см. A3.0 п.5).
2. **Дубль React-копий** через workspace-резолв. Vite/npm обычно резолвят
   корректно (один React в `frontend/node_modules`), но если увидите
   «Invalid hook call» — это оно. Лечится `peerDependencies` в shared.
3. **`verbatimModuleSyntax: true`** в shared-tsconfig — потребует `import type`
   везде, где импортируется только тип. Это и в owner так же — единый стиль.
4. **CSS-классы внутри компонентов используют брендовые токены** (например,
   `bg-accent`). Если эти классы используются ТОЛЬКО в shared/ui (нигде в
   owner src/) — Tailwind должен их собрать из shared-content globe. Если
   забыли — увидите потерянные цвета.
5. **Sync-тест tokens (sync-тест от A1)** должен продолжать проходить — не
   переименовывать ключи в tokens.ts без зеркального обновления tokens.css.
6. **Shim в owner/src/shared/ui** не нужен в финале — удалить после массовой
   замены импортов. Этап «массовая замена» — отдельный коммит, чтобы diff
   читался.

#### A3.6 — Definition of Done

- [ ] `frontend/shared/package.json` экспортирует `./ui`.
- [ ] `frontend/shared/tsconfig.json` существует, `npm run typecheck --workspace shared` ✓.
- [ ] Все 7 существующих примитивов перенесены, owner-shim работает (или импорты уже заменены).
- [ ] Добавлено минимум: `Stack`, `Field`, `Textarea`, `Select`, `Badge`, `Spinner` (это разблокирует A5).
- [ ] На каждый новый компонент vitest зелёный.
- [ ] Tailwind `content` в owner-конфиге включает `../shared/ui/**/*.{ts,tsx}`.
- [ ] ESLint правило `no-restricted-imports` запрещает deep-импорты `@guard-reviews/shared/ui/*`.
- [ ] `npm run typecheck/lint/test/build --workspace owner` все зелёные.
- [ ] CI обновлён: добавлен `typecheck --workspace shared`.
- [ ] Sync-тест tokens.ts ↔ tokens.css продолжает проходить.

### Фаза A4. Utility-классы в `scan/render.ts` — ✅ СДЕЛАНО

> Артефакты:
> - `frontend/scan/src/scan/styles.ts` — объект `scanStyles` с константами utility-классов
>   (DRY: общая база `fieldControl`/`button` факторизована, специализации `primaryButton`/
>   `platformButton`/`fieldTextarea` через шаблонные литералы).
> - `frontend/scan/src/scan/render.ts` — все классы импортятся как `scanStyles as s`.
> - `frontend/scan/src/styles/main.css` — `@layer components` удалён, остался только
>   `@layer base` (html/body/#app) + `#captcha-container` height-fix.
> - Pseudo-state'ы: `card--background::before` → `before:content-[''] before:absolute before:inset-0 before:bg-gradient-to-b before:from-black/35 before:to-black/65 [&>*]:relative [&>*]:z-[1]`. Без отдельного CSS-блока.
> - Disabled-вариант primary-button: `enabled:hover:brightness-105 disabled:opacity-55 disabled:cursor-not-allowed` (Tailwind `enabled:`/`disabled:` варианты вместо `:hover:not(:disabled)`).
> - Inputs/textareas: `font-sans` вместо `font: inherit` (визуально эквивалентно — body уже `font-sans`).
> - Bundle: `npm run build --workspace scan` зелёный, CSS 12.04 KB (vs 10.27 KB на @apply-рецептах).

### Фаза A4 (исторический контекст). CSS-«рецепты» в `frontend/scan/`

Так как scan — vanilla, нужны готовые CSS-классы под `@layer components` в
`frontend/scan/src/styles/main.css`:
```css
@layer components {
  .surface-card {
    @apply bg-surface shadow-soft rounded-2xl p-5 sm:p-6;
  }
  .btn-primary {
    @apply inline-flex items-center justify-center gap-2 rounded-xl
           bg-accent px-4 py-2.5 text-sm font-medium text-accent-fg
           transition hover:brightness-105 focus-visible:outline
           focus-visible:outline-2 focus-visible:outline-offset-2
           focus-visible:outline-accent;
  }
  .btn-ghost { /* … */ }
  .input-base { /* … */ }
  .field-label { @apply block text-xs font-medium text-ink-500; }
  .field-error { @apply text-xs text-danger; }
}
```

Эти классы — публичный контракт для scan-разметки в `render.ts`. Заменить
существующие inline-стили на брендовые классы.

**Definition of done:**
- Локальный smoke-тест: открыть `/s/{slug}/...` страницу, проверить вёрстку.

### Фаза A5. Применение в owner — ✅ СДЕЛАНО

> Артефакты:
> - `ProfileForm` — 3 тройки `label+Input+error` → `<Field label htmlFor error>`;
>   `subdomainChanged` warning остался кастомным `<p class="text-warning">` внутри `<Field>` (Field-hint рисует gray ink-500, а нам нужен warning).
> - `PlaceForm` — 2 поля `Card>label>span+Input` → `<Field>`; vanilla `<select>` → `<Select>` из shared с aria-label'ом; submit + кнопки получают `<Spinner>` рядом с текстом.
> - `CodeForm` — `<label>+Input+conditional p` → `<Field>` с `error`/`hint` (взаимоисключающие, см. `error: exchange.isError ? ... : undefined, hint: !isError ? ... : undefined`).
> - `ReviewCard` — `statusToneClass` (record class-string) → `statusTone: Record<status, BadgeTone>` + `<Badge tone>`. Тип `BadgeTone` импортируется из `@/shared/ui`.
> - `PaymentsHistory` — то же, для `OwnerPayment['status']`.
> - `TelegramCodeCard`, `ExtendSubscriptionButton`, `ProfileForm`, `PlaceForm`, `CodeForm` — `<Spinner size="sm" />` показывается рядом с pending-надписью (использует `gap-2` базовой `Button`).
>
> Lint/typecheck/test (47/47) — зелёные. CSS bundle owner: 19.16 → 18.97 KB.

### Фаза A5 (исторический контекст). Применение в owner (рефакторинг)

После A1–A4 пройтись по существующим компонентам owner и:
- Заменить копипаст-`space-y-*`/`flex gap-*` на `<Stack>`.
- Заменить `<label>+<input>+<p.text-danger>` тройки на `<Field>`.
- Заменить ad-hoc badge'ы (`statusToneClass` в `ReviewCard`) на `<Badge>`.
- Заменить ad-hoc dropdown в `StatusSwitcher` на shared-`Popover`/`Menu`,
  если такой добавим (опционально — пока работает).

**Definition of done:**
- Vitest все зелёные.
- Lint без warnings.
- Скриншоты страниц до/после идентичны.

---

### Фаза B1. Domain `Feature` enum

**Файл:** `backend/app/Domain/Iam/Feature.php`
```php
namespace App\Domain\Iam;

enum Feature: string
{
    case MultiplePlaces = 'multiple_places';
    case WeeklyDigest = 'weekly_digest';
    case NegativeAlertsTelegram = 'negative_alerts_telegram';
    case NegativeAlertsEmail = 'negative_alerts_email';
    case CustomBranding = 'custom_branding';
    case QrThemes = 'qr_themes';
    case CsvExportReviews = 'csv_export_reviews';
    case ApiAccess = 'api_access';
    case PrioritySupport = 'priority_support';

    /** @return list<self> */
    public static function all(): array { return self::cases(); }

    public function label(): string
    {
        return match ($this) {
            self::MultiplePlaces => 'Несколько точек',
            self::WeeklyDigest => 'Еженедельный дайджест',
            // …
        };
    }
}
```

**Расширить `Tariff` VO:**
```php
final readonly class Tariff
{
    /**
     * @param  array<int, Feature>  $features
     */
    public function __construct(
        public TariffId $id,
        public string $title,
        public int $basePrice = 0,
        public int $extraPlacePrice = 0,
        public bool $isDefault = false,
        public ?int $placesLimit = null,
        public array $features = [],
    ) {}

    public function hasFeature(Feature $feature): bool
    {
        foreach ($this->features as $f) {
            if ($f === $feature) return true;
        }
        return false;
    }
}
```

**`TariffMapper::toDomain()`** — JSON → list of Feature, ignoring unknowns:
```php
$rawFeatures = is_array($model->features) ? $model->features : [];
$features = [];
foreach ($rawFeatures as $value) {
    // Legacy ключи в виде ассоц-массива {extra_place_price: 29000} пропускаем.
    if (! is_string($value)) continue;
    $f = Feature::tryFrom($value);
    if ($f !== null) $features[] = $f;
}
```

> Внимание: текущий seeder пишет `features = ['extra_place_price' => 29000]`.
> Это assoc-array, mapper его игнорит (ключи не строки в значениях). Это OK —
> `extra_place_price` уже мигрировал в отдельную колонку `tariffs.extra_place_price`
> (см. фазу 3 owner-plan). Перепишем seeder в Фазе B5.

**Tests:** unit на `Feature::cases()`, `Tariff::hasFeature()`, mapper'е
(пустой массив, валидные, невалидные, legacy ассоц).

### Фаза B2. Use case `GetOwnerFeatures`

**Файлы:** `backend/app/Application/Iam/GetOwnerFeatures/`
- `GetOwnerFeaturesQuery.php` — `ownerId`.
- `GetOwnerFeaturesHandler.php` — резолвит owner → tariff (с default-fallback) → `$tariff->features`. Возвращает `array<Feature>`.
- Тест: с tariff'ом, без tariff'а (default), полный enum-coverage не нужен.

**Bind в `IamServiceProvider`** — handler автоматически через autowire,
никаких ручных bindings.

### Фаза B3. HTTP middleware `feature:<key>`

**Файл:** `backend/app/Http/Middleware/RequireFeature.php`
```php
final class RequireFeature
{
    public function __construct(
        private readonly GetOwnerFeaturesHandler $getFeatures,
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $feature = Feature::tryFrom($featureKey);
        if ($feature === null) {
            throw new InvalidArgumentException("Unknown feature key: {$featureKey}");
        }

        /** @var User|null $user */
        $user = $request->user('owner');
        if ($user === null) {
            return ApiResponse::error(ApiErrorCode::Unauthenticated, 401);
        }

        $features = $this->getFeatures->handle(
            new GetOwnerFeaturesQuery(ownerId: (string) $user->id),
        );

        if (! in_array($feature, $features, true)) {
            return ApiResponse::error(ApiErrorCode::FeatureNotAvailable, 403);
        }

        return $next($request);
    }
}
```

Регистрация alias в `bootstrap/app.php`:
```php
'feature' => RequireFeature::class,
```

Новый `ApiErrorCode::FeatureNotAvailable = 'feature_not_available'` с
сообщением «Эта функция недоступна на вашем тарифе.».

**Применение** в `routes/api.php`:
```php
Route::middleware('feature:multiple_places')->group(function () {
    Route::post('places', [OwnerPlacesController::class, 'store']);
});
```

> **Subscription vs Feature ordering.** Для платных мутаций уже стоит
> `subscription.active:402`. Feature применяется **внутри** этой группы.
> Порядок такой: `auth:owner` → `subscription.active:402` → `feature:<key>`.
> Тогда пользователь сначала видит "оплати подписку", и только потом
> "feature недоступна". В тестах закрепить порядок.

**GET-endpoint** для frontend:
- `GET /api/owner/features` → `{ features: ["multiple_places", ...] }`.
  Контроллер: `OwnerFeaturesController` (один метод).

### Фаза B4. Filament: чекбоксы вместо KeyValue

**`backend/app/Filament/Resources/Tariffs/Schemas/TariffForm.php`:**
```php
use App\Domain\Iam\Feature;
use Filament\Forms\Components\CheckboxList;
// …
CheckboxList::make('features')
    ->label('Возможности тарифа')
    ->options(collect(Feature::cases())->mapWithKeys(
        fn (Feature $f) => [$f->value => $f->label()],
    )->all())
    ->columns(2)
    ->helperText('Снимите/поставьте галки, чтобы поменять доступ.'),
```

**Filament сохранит как JSON-массив строк** — это и есть формат, который ждёт
`TariffMapper`. `KeyValue` уходит.

`TariffInfolist`/`TariffsTable` — обновить, если показывают features колонкой
(сейчас, кажется, не показывают; перепроверить).

**Feature-тест** в `tests/Feature/Admin/TariffResourceTest.php`:
- create tariff с фичами → сохраняется как JSON-array.
- edit tariff, снять фичу → сохраняется без неё.

### Фаза B5. Cleanup legacy `features.extra_place_price`

В seeder'ах и factory сейчас:
```php
'features' => ['extra_place_price' => config(...)]
```
Это assoc-массив, `TariffMapper` его уже игнорирует (см. B1). Но мусор в БД
неприятен.

**Шаги:**
1. `TariffSeeder` — поменять на `'features' => [Feature::MultiplePlaces->value]` (или пусто).
2. `TariffFactory` — то же.
3. Миграция `2026_XX_XX_normalize_tariff_features.php` — пройтись по всем
   `tariffs`, если `features` ассоц или `null` → переписать в `[]`. Делать через
   data-migration, не схему.

### Фаза B6. Frontend — entities/features

**Файлы:**
- `frontend/owner/src/entities/features/model/types.ts`:
  ```ts
  // Зеркалит backend Feature enum. Источник — backend; сюда копируем руками
  // (UI-stub). Тест проверяет совпадение списка с backend через snapshot.
  export type Feature =
    | 'multiple_places'
    | 'weekly_digest'
    | 'negative_alerts_telegram'
    | 'negative_alerts_email'
    | 'custom_branding'
    | 'qr_themes'
    | 'csv_export_reviews'
    | 'api_access'
    | 'priority_support';
  ```
- `frontend/owner/src/entities/features/config/queryKeys.ts`:
  ```ts
  export const featuresQueryKeys = {
    all: ['features'] as const,
    list: () => [...featuresQueryKeys.all, 'list'] as const,
  };
  ```
- `frontend/owner/src/entities/features/api/useFeaturesQuery.ts`:
  ```ts
  export const useFeaturesQuery = (): UseQueryResult<Set<Feature>> =>
    useQuery({
      queryKey: featuresQueryKeys.list(),
      queryFn: async () => {
        const r = await httpClient.get<{ data: { features: Feature[] } }>('/features');
        return new Set(r.data.data.features);
      },
      staleTime: 5 * 60_000,
    });
  ```
- `frontend/owner/src/entities/features/model/useFeature.ts`:
  ```ts
  export const useFeature = (flag: Feature): boolean => {
    const q = useFeaturesQuery();
    return q.data?.has(flag) ?? false;
  };
  ```
- `frontend/owner/src/entities/features/ui/FeatureGate.tsx`:
  ```tsx
  type Props = {
    feature: Feature;
    fallback?: ReactNode;
    children: ReactNode;
  };
  export function FeatureGate({ feature, fallback = null, children }: Props) {
    return useFeature(feature) ? <>{children}</> : <>{fallback}</>;
  }
  ```
- `frontend/owner/src/entities/features/ui/UpsellCard.tsx` — карточка
  «фича доступна в …, обновите тариф» с CTA на `/subscription`. Используется
  как fallback в `<FeatureGate>`.
- `frontend/owner/src/entities/features/index.ts` — public API.

**Тесты vitest:**
- `useFeature` возвращает true/false на основе кеша React Query.
- `<FeatureGate>` рендерит children/fallback корректно.

### Фаза B7. Применение feature-gate в Owner SPA

Опираясь на стартовый список фич:

- `multiple_places`:
  - Route `/places/new` под guard'ом + UpsellCard.
  - Кнопка «Добавить точку» в `PlacesListPage` — `<FeatureGate>`.
  - Backend: `Route::middleware('feature:multiple_places')` на
    `POST /api/owner/places`.
- `weekly_digest`, `negative_alerts_telegram`, `negative_alerts_email` —
  в `ProfilePage` секция «Уведомления» (новый widget): чекбоксы под
  `<FeatureGate>` каждый, без фичи — disabled + tooltip «в премиум-тарифе».
  Реальное переключение каналов — отдельный use case (out of scope, см. § 6).
- `custom_branding` / `qr_themes` — пока заглушки, не применяем на UI.
- `csv_export_reviews` — кнопка «Экспорт CSV» в `widgets/reviews-table`
  под gate. Backend endpoint = новый use case, опционально.

**Принцип:** где `<FeatureGate>` рисует UpsellCard, там же на backend стоит
`feature:<key>` middleware. Согласованность через **feature-тест**:
endpoint без фичи → 403 `feature_not_available`; с фичей → 200.

### Фаза B8. Bonus — scan может использовать feature flags

Если будут scan-side фичи (`custom_branding`, `qr_themes`), их надо отдавать
вместе с публичной информацией о Place:
- Расширить `GET /api/public/places/{place}` (или `ResolvePublicPlace`) полем
  `tariff_features: string[]` (только relevant-фичи, не все).
- Scan читает и условно рендерит брендинг.
- В Domain: новый use case `GetPublicPlaceFeatures(placeId)` или extension
  `GetPublicPlaceView`. Сейчас Place уже резолвит Owner — добавить features
  туда.

**Это можно отложить** до момента когда реально появится первая scan-side
фича. Сейчас — заглушка.

---

## 4. Тестовая стратегия

### Backend (per phase)
- **Domain** — unit на `Feature`, `Tariff::hasFeature`, `TariffMapper::toDomain` (Pest).
- **Application** — unit на `GetOwnerFeaturesHandler` (fake repos, проверка default-fallback).
- **Middleware** — feature-тест в `tests/Feature/Owner/OwnerFeatureGuardTest.php`:
  - 401 без сессии,
  - 403 + code `feature_not_available` без фичи в тарифе,
  - 200 с фичей,
  - порядок `subscription:402 → feature:403` (если без подписки → 402, не 403).
- **Filament** — обновить `tests/Feature/Admin/TariffResourceTest.php`.

### Frontend
- **vitest** для `useFeature`, `<FeatureGate>` (моки React Query кеша).
- **vitest** для всех новых shared/ui компонентов (Field, Switch, Modal, …):
  рендер + aria + клик.
- **Visual smoke** (не автоматизирован): перед merge — открыть локально
  dashboard, places, reviews, subscription, profile, login и убедиться, что
  ничего не сломалось.

### CI
- Уже есть в `.github/workflows/ci.yml` (фаза 8 owner-plan).
- Добавить job `frontend-scan`: `npm run build --workspace scan` после
  Tailwind-миграции (А2), чтобы регрессии вёрстки ловились на PR.

---

## 5. Подводные камни (anticipate, не наступать)

> Уроки A1+A2 уже выписаны в блоке «Текущий статус» (8 пунктов). Ниже —
> уроки, ожидаемые на A3/B-фазах.

1. **`KeyValue` → `CheckboxList` в Filament.** Старые tariff-записи имеют
   `features` как assoc-массив. Filament при загрузке `CheckboxList`'a с
   options-keys типа `multiple_places` **не упадёт**, но покажет пусто —
   потому что значения там сейчас другие. Нужна **B5 миграция данных
   ДО** Фазы B4 (или в один PR с ней).

2. **`TariffMapper` обратная совместимость.** Если массив `features` — ассоц
   (legacy), `Feature::tryFrom($value)` падает (значения там — числа). Mapper
   должен **typecheck → string → tryFrom**, и тихо ронять non-strings (см. B1).
   Не упасть на старых данных в продакшене.

3. **Default-tariff fallback.** Если у owner'а нет привязанного тарифа
   (`users.tariff_id = null`), `GetOwnerFeatures` должен брать
   `findDefault()`. Если default тоже нет — возвращать `[]`. Не падать.

4. **Subscription guard и feature guard порядок.** `subscription.active:402`
   ставится первым. `feature:<key>` — после. Иначе пользователь без подписки
   будет видеть 403 «нет фичи», хотя проблема — оплата.

5. **Feature key стабильность.** Strings в enum-backing — это контракт с БД.
   Переименование = миграция данных. Делать только через deprecation:
   1. Добавить новый case с новым value, оба указывают на одно UI-описание;
   2. Миграция переписывает строки в БД;
   3. Удалить старый case.

6. **`useFeature` до загрузки кеша возвращает `false`.** Это значит на
   первой отрисовке gated-UI окажется скрыт. Решения:
   - Suspense (тяжело без переделки query).
   - Prefetch в `RequireSession` (рекомендуется — добавить
     `queryClient.prefetchQuery(featuresQueryKeys.list(), ...)`).
   - `useFeature(flag, { default: 'show' })` — рискованно, может на миг
     показать недоступное.

   **Выбрано:** prefetch в `RequireSession` (одна точка, чисто).

7. **CSS variables vs Tailwind preset.** Не пытаться синхронизировать
   автоматически (через JS generation в CSS). Делать руками — единая правка
   в обоих местах. Чтобы это не разваливалось — vitest-snapshot теста на
   количество и имена токенов в `tokens.css` vs `design-tokens.ts`.

8. **scan не использует React.** Не пытаться засунуть React-компоненты в
   scan. Только CSS-классы + DOM-helpers. Если очень нужно компонентное —
   делайте через `customElements.define(...)` (web-components),
   но **не сейчас**, KISS.

9. **`frontend/shared` — workspace, не npm-пакет.** TypeScript paths +
   monorepo workspace; consumer'ы получают исходники, не бандл. Это значит:
   - `frontend/shared/tsconfig.json` нужен (если ещё нет).
   - `frontend/owner/tsconfig.json` должен включать paths `@guard-reviews/shared/*`.
   - Vitest и Vite корректно резолвят (Vite через workspace по умолчанию).

10. **Backwards-compat запроса `/api/owner/features`.** Если SPA уже
    задеплоен, а backend ещё не обновлён — `useFeaturesQuery` упадёт на
    404. Решение: на `404` возвращать пустой `Set<Feature>` (всё закрыто).
    На production деплой backend **перед** frontend.

---

## 6. Что НЕ делаем в этой сессии (явный out of scope)

- **Уведомления по каналам** (`toggle` per-канал в профиле) — есть use case
  `MultiChannelOwnerNotifier`, но переключение каналов owner'ом — отдельная
  фича. В этой сессии только feature-gate (запрет/разрешение), UI-toggle
  каналов = отдельный PR.
- **CSV экспорт отзывов** — endpoint + работающая выгрузка. В этой сессии
  только gate (кнопка под `<FeatureGate>`, реальный handler — потом).
- **Custom branding / QR-themes** — рендер брендинга на scan, конфигуратор
  в owner-панели. Не делаем, только feature-keys заведены.
- **API access** — реальный публичный API + PAT. Только feature-key.
- **Headless UI либ** (Radix, Ariakit) — НЕ берём. Свои тонкие примитивы.
- **Theming runtime (dark mode)** — токены через CSS variables это позволят,
  но dark mode выкатываем отдельным PR. В этой сессии — только light.

---

## 7. Definition of Done всей сессии

- [ ] `frontend/shared/design-tokens.ts` + `tailwind/preset.ts` + `styles/tokens.css` существуют, экспортируются через `package.json`.
- [ ] `frontend/owner` и `frontend/scan` используют `presets: [preset]`, дублирующиеся токены удалены.
- [ ] `frontend/shared/ui/` содержит минимум: Button, Card, Input, Textarea, Select, Switch, Checkbox, Radio, Field, Stack, Skeleton, EmptyState, Badge, Alert, Banner, Modal, BottomSheet, Tabs, Avatar, Spinner, Pagination, StatTile, ConfirmDialog, Sparkline.
- [ ] `frontend/owner/src/shared/ui/` — пустой или удалён, всё импортируется из shared.
- [ ] `frontend/scan` собирается с Tailwind, использует брендовые классы.
- [ ] `App\Domain\Iam\Feature` enum + `Tariff::hasFeature()` + mapper в `Tariff` пробрасывает features.
- [ ] `GetOwnerFeaturesHandler` + `GET /api/owner/features` отдают список.
- [ ] `RequireFeature` middleware + `feature:<key>` alias + `ApiErrorCode::FeatureNotAvailable`.
- [ ] Filament `TariffResource` редактирует features через `CheckboxList`.
- [ ] `entities/features` в owner-SPA: `useFeature`, `<FeatureGate>`, `<UpsellCard>`.
- [ ] `RequireSession` prefetch'ит `featuresQueryKeys.list()`.
- [ ] Минимум один реальный gate применён: `multiple_places` блокирует `POST /api/owner/places` + скрывает кнопку «Добавить точку».
- [ ] Backend feature-test `OwnerFeatureGuardTest` зелёный.
- [ ] Frontend vitest + lint + typecheck зелёные.
- [ ] CI workflow обновлён: добавлен job `frontend-scan` build.
- [ ] `backend/docs/owner-panel.md` обновлён: § 4 (новый endpoint), § 6 (паттерн добавления feature-gate), § 8 (порядок subscription→feature guards).
- [ ] `backend/саммари.md` упоминает Feature и Design System кратко.

---

## 8. Cheatsheet (запуск/проверка)

```bash
# Frontend
cd /Users/rasa/dev/rws/frontend && npm i  # один раз после изменения package.json'ов
cd frontend/owner && npm run lint && npm run typecheck && npm run test && npm run build
cd frontend/scan  && npm run typecheck && npm run build

# Backend (PHP 8.4 — только в Docker)
cd /Users/rasa/dev/rws && docker run --rm -v "$(pwd)/backend:/app" -w /app \
  php:8.4-cli-alpine sh -c "apk add --no-cache icu-libs icu-dev libzip-dev zip >/dev/null 2>&1 \
  && docker-php-ext-install intl pdo_pgsql >/dev/null 2>&1 \
  && php artisan test 2>&1"

# Pint (можно с хоста)
cd backend && ./vendor/bin/pint --test app/Domain/Iam/Feature.php \
  app/Application/Iam/GetOwnerFeatures app/Http/Middleware/RequireFeature.php
```

---

## 9. Эталоны «как у нас принято»

| Делаю...                            | Смотри...                                                                             |
|-------------------------------------|---------------------------------------------------------------------------------------|
| Domain enum                         | `App\Enums\ReviewStatus`, `App\Domain\Payments\PaymentStatus`                         |
| Domain VO с массивом                | `App\Domain\Iam\Tariff` (текущий) — расширяем по образцу                              |
| Mapper VO ↔ Eloquent с JSON         | `App\Infrastructure\Persistence\Eloquent\Iam\TariffMapper` — расширяем                |
| Query use case с fallback           | `App\Application\Iam\GetOwnerSubscription\GetOwnerSubscriptionHandler` (resolveTariff) |
| HTTP middleware с параметром        | `App\Http\Middleware\EnsureSubscriptionActive` (умеет `:402`)                          |
| `ApiErrorCode` добавление           | `App\Enums\ApiErrorCode` — case + message в `match`                                   |
| Filament Section c CheckboxList     | Нет прецедента — добавляем; см. Filament docs                                          |
| Feature-test middleware              | `tests/Feature/Owner/OwnerSecurityTest.php` (subscription guard)                      |
| React Query entity                  | `frontend/owner/src/entities/subscription/*`                                          |
| FP-only error class                  | `features/update-profile/api/useUpdateProfileMutation.ts` (`isProfileValidationError`)|
| Shared workspace export              | `frontend/shared/package.json` (`exports` field)                                      |
| Tailwind tokens                      | `frontend/owner/tailwind.config.ts` (переносим в shared)                              |

---

## 10. Открытые вопросы (решить с продуктом до старта)

1. **Финальный список Feature'ов.** Сейчас стартовый набор из § 2 — гипотеза.
   Закрепить с продактом перед B1 (название case-ов).
2. **Default-feature-set для owner'ов без тарифа** — пусто или
   «бесплатный базовый»? Решить в B2.
3. **Гранулярность channel-toggle'ов** — на одну фичу `notifications_telegram`/
   `notifications_email` или две? Сейчас в плане — две (см. § 2).
4. **CSV export** — фича целиком или с лимитом «N экспортов в месяц»?
   Если лимит — нужен `entities/feature-quotas/` (вне scope этой сессии).
5. **Брендинг scan** — поднимаем на эту сессию или только feature-key
   завести? Сейчас — только key.
