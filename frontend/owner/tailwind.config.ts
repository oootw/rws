import type { Config } from 'tailwindcss';

import { preset } from '@guard-reviews/shared/tailwind';

/**
 * Owner-панель. Брендинг (цвета/тени/радиусы/шрифты) — в shared preset
 * (`frontend/shared/design-tokens.ts`). Здесь — только глобы под content.
 */
const config: Config = {
  content: [
    './index.html',
    './src/**/*.{ts,tsx}',
    '../shared/ui/**/*.{ts,tsx}',
  ],
  presets: [preset as Config],
};

export default config;
