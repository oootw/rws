/**
 * Sync-тест: значения в shared/styles/tokens.css должны точно соответствовать
 * shared/design-tokens.ts. Иначе любой из источников рискует разойтись с
 * другим (Tailwind preset берёт из TS, vanilla scan — из CSS).
 *
 * Покрываем только колонки токенов, у которых однозначное соответствие имени.
 * Шрифты и motion проверяем мягче — по наличию маркеров.
 */
import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

import { colors, radii, shadows, motion, typography } from '@guard-reviews/shared/design-tokens';

const TOKENS_CSS_PATH = resolve(
  dirname(fileURLToPath(import.meta.url)),
  '../../../shared/styles/tokens.css',
);

const tokensCss = readFileSync(TOKENS_CSS_PATH, 'utf8');

function readVar(name: string): string | null {
  const match = tokensCss.match(new RegExp(`--${name}:\\s*([^;]+);`));
  return match === null ? null : match[1].trim();
}

describe('design-tokens.ts ↔ tokens.css sync', () => {
  it('цвета синхронизированы', () => {
    expect(readVar('color-canvas')).toBe(colors.canvas);
    expect(readVar('color-surface')).toBe(colors.surface);

    for (const [shade, hex] of Object.entries(colors.ink)) {
      expect(readVar(`color-ink-${shade}`)).toBe(hex);
    }

    for (const family of ['accent', 'danger', 'warning', 'success'] as const) {
      expect(readVar(`color-${family}`)).toBe(colors[family].DEFAULT);
      expect(readVar(`color-${family}-fg`)).toBe(colors[family].fg);
      expect(readVar(`color-${family}-soft`)).toBe(colors[family].soft);
    }
  });

  it('радиусы синхронизированы', () => {
    for (const [key, value] of Object.entries(radii)) {
      expect(readVar(`radius-${key}`)).toBe(value);
    }
  });

  it('тени синхронизированы', () => {
    expect(readVar('shadow-soft')?.replace(/\s+/g, ' ')).toBe(shadows.soft.replace(/\s+/g, ' '));
    expect(readVar('shadow-lift')?.replace(/\s+/g, ' ')).toBe(shadows.lift.replace(/\s+/g, ' '));
  });

  it('motion-токены содержат значения из TS (cubic-bezier совпадает)', () => {
    // Сравниваем по «функциональному ядру», игнорируя пробелы.
    for (const key of ['fast', 'base', 'slow'] as const) {
      const cssValue = readVar(`motion-${key}`)?.replace(/\s+/g, ' ');
      const tsValue = motion[key].replace(/\s+/g, ' ');
      expect(cssValue).toBe(tsValue);
    }
  });

  it('font-стек содержит все имена из typography', () => {
    const fontSans = readVar('font-sans') ?? '';
    for (const family of typography.fontSans) {
      // Семейства с пробелами в CSS бывают в кавычках — проверяем без них.
      const bare = family.replace(/^"|"$/g, '');
      expect(fontSans).toContain(bare);
    }
  });
});
