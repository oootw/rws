import type { Config } from 'tailwindcss';

import { preset } from '@guard-reviews/shared/tailwind';

/**
 * Scan-страница (vanilla TS). Брендинг наследуется из shared preset
 * (`frontend/shared/design-tokens.ts`). Здесь — только глоб под content.
 *
 * Templates берутся из render.ts (генерируется HTML-строка) — поэтому в
 * content нужны .ts файлы тоже.
 */
const config: Config = {
  content: ['./index.html', './src/**/*.{ts,html}'],
  presets: [preset as Config],
};

export default config;
