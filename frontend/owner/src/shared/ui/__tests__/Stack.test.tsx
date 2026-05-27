import { describe, expect, it } from 'vitest';
import { render } from '@testing-library/react';

import { Stack } from '@guard-reviews/shared/ui';

describe('Stack', () => {
  it('по умолчанию рендерит flex-col с gap-3', () => {
    const { container } = render(
      <Stack>
        <span>a</span>
      </Stack>,
    );
    const root = container.firstChild as HTMLElement;
    expect(root.className).toContain('flex-col');
    expect(root.className).toContain('gap-3');
  });

  it('применяет direction=row, gap=6, align=center и тег as=section', () => {
    const { container } = render(
      <Stack as="section" direction="row" gap={6} align="center" justify="between">
        <span>a</span>
      </Stack>,
    );
    const root = container.firstChild as HTMLElement;
    expect(root.tagName).toBe('SECTION');
    expect(root.className).toContain('flex-row');
    expect(root.className).toContain('gap-6');
    expect(root.className).toContain('items-center');
    expect(root.className).toContain('justify-between');
  });
});
