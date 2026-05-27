/**
 * Guard Reviews — дизайн-токены (Kvell Merchant).
 *
 * Единая точка правды для брендинга обоих фронтов: `frontend/owner` (React)
 * и `frontend/scan` (vanilla TS). Не меняйте значения здесь в одиночку —
 * синхронизируйте с `styles/tokens.css` (см. unit-тест в `tests/`,
 * который проверяет соответствие).
 *
 * Структура отражает Tailwind theme.extend, чтобы preset мог строиться
 * без преобразований.
 */

export const colors = {
  canvas: '#FAFAF7',
  surface: '#FFFFFF',
  ink: {
    900: '#0F172A',
    700: '#334155',
    500: '#64748B',
    400: '#94A3B8',
    200: '#E2E8F0',
    100: '#F1F5F9',
  },
  accent: {
    DEFAULT: '#10B981',
    fg: '#FFFFFF',
    soft: '#ECFDF5',
  },
  danger: {
    DEFAULT: '#F97066',
    fg: '#FFFFFF',
    soft: '#FEF2F2',
  },
  warning: {
    DEFAULT: '#F59E0B',
    fg: '#1F2937',
    soft: '#FFFBEB',
  },
  success: {
    DEFAULT: '#10B981',
    fg: '#FFFFFF',
    soft: '#ECFDF5',
  },
} as const;

export const radii = {
  md: '0.75rem',
  xl: '1rem',
  '2xl': '1rem',
  '3xl': '1.5rem',
  full: '9999px',
} as const;

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
  fontSans: [
    'Inter',
    'Manrope',
    '-apple-system',
    'BlinkMacSystemFont',
    'Segoe UI',
    'sans-serif',
  ],
  fontMono: [
    'ui-monospace',
    'SFMono-Regular',
    'Menlo',
    'Consolas',
    'monospace',
  ],
} as const;

export const sizes = {
  shell: '1200px',
} as const;

/**
 * Полный набор токенов одним объектом — удобно для тестов и кодогена.
 */
export const tokens = {
  colors,
  radii,
  shadows,
  motion,
  typography,
  sizes,
} as const;

export type DesignTokens = typeof tokens;
