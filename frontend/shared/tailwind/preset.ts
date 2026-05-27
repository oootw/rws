import type { Config } from 'tailwindcss';

import { colors, radii, shadows, motion, sizes, typography } from '../design-tokens';

/**
 * Общий Tailwind-preset Guard Reviews.
 *
 * Подключение в consumer-конфиге:
 *   import { preset } from '@guard-reviews/shared/tailwind';
 *   export default { content: [...], presets: [preset] } satisfies Config;
 *
 * Никаких `content` здесь — это ответственность consumer'а
 * (Tailwind в monorepo не умеет наследовать content от preset'а).
 */
export const preset: Pick<Config, 'theme'> = {
  theme: {
    extend: {
      colors,
      borderRadius: radii,
      boxShadow: shadows,
      fontFamily: {
        sans: [...typography.fontSans],
        mono: [...typography.fontMono],
      },
      transitionTimingFunction: {
        kvell: 'cubic-bezier(0.4, 0, 0.2, 1)',
      },
      transitionDuration: {
        fast: '120ms',
        base: '200ms',
        slow: '320ms',
      },
      maxWidth: {
        shell: sizes.shell,
      },
    },
  },
};

/**
 * Re-export, чтобы тесты и потенциальный кодогенератор имели прямой доступ
 * без дублирования imports.
 */
export { colors, radii, shadows, motion, sizes, typography } from '../design-tokens';
