import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';

import { Badge } from '@guard-reviews/shared/ui';

describe('Badge', () => {
  it('рендерит neutral по умолчанию', () => {
    render(<Badge>Тест</Badge>);
    const node = screen.getByText('Тест');
    expect(node.className).toContain('bg-ink-100');
    expect(node.className).toContain('text-ink-500');
  });

  it('применяет tone=danger', () => {
    render(<Badge tone="danger">Ошибка</Badge>);
    const node = screen.getByText('Ошибка');
    expect(node.className).toContain('text-danger');
  });

  it('применяет tone=warning', () => {
    render(<Badge tone="warning">Внимание</Badge>);
    const node = screen.getByText('Внимание');
    expect(node.className).toContain('text-warning');
  });
});
