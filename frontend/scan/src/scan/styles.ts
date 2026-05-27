/**
 * Tailwind utility-class пресеты для scan-разметки.
 *
 * Это «рецепты» в TypeScript вместо @layer components в CSS:
 * - один источник правды живёт рядом с render.ts,
 * - Tailwind видит все классы (scan content включает src/**\/*.ts) и tree-shake'ит,
 * - типы дают авто-комплит вместо строковых литералов в DOM.
 */

const fieldControl =
  'w-full bg-surface text-ink-900 border border-ink-200 rounded-xl px-4 py-3 text-base font-sans focus:outline-none focus:border-accent focus:ring-4 focus:ring-accent/15';

const button =
  'appearance-none w-full border-0 rounded-xl px-4 py-3.5 text-base font-semibold cursor-pointer transition-[filter] duration-fast ease-kvell';

export const scanStyles = {
  page: 'min-h-screen flex items-center justify-center p-6',
  card: 'w-full max-w-[420px] bg-surface rounded-3xl p-7 shadow-soft',
  cardBackground:
    "text-surface relative overflow-hidden bg-cover bg-center before:content-[''] before:absolute before:inset-0 before:bg-gradient-to-b before:from-black/35 before:to-black/65 [&>*]:relative [&>*]:z-[1]",
  title: 'mt-0 mb-2 text-2xl font-bold text-center',
  subtitle: 'mt-0 mb-6 text-center opacity-85',
  message: 'm-0 text-center',
  privacyLink: 'block mt-4 text-center text-xs opacity-75',
  stars: 'flex justify-center gap-2 mt-4',
  starButton:
    'appearance-none border-0 bg-transparent text-3xl leading-none cursor-pointer p-1 text-warning transition-transform duration-fast ease-kvell hover:scale-110 focus-visible:scale-110',
  platformList: 'grid gap-3',
  platformButton: `${button} bg-ink-100 text-ink-900 hover:bg-ink-200`,
  primaryButton: `${button} bg-accent text-accent-fg enabled:hover:brightness-105 disabled:opacity-55 disabled:cursor-not-allowed`,
  field: 'grid gap-1.5 mb-4',
  fieldLabel: 'text-sm font-semibold',
  fieldInput: fieldControl,
  fieldTextarea: `${fieldControl} min-h-[120px] resize-y`,
  consent: 'flex gap-2.5 items-start mb-4 text-sm',
  consentLink: 'text-inherit underline',
} as const;
