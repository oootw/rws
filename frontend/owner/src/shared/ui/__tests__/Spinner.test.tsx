import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';

import { Spinner } from '@guard-reviews/shared/ui';

describe('Spinner', () => {
  it('имеет role=status и дефолтный aria-label', () => {
    render(<Spinner />);
    const node = screen.getByRole('status');
    expect(node.getAttribute('aria-label')).toBe('Загрузка');
    expect(node.className).toContain('animate-spin');
  });

  it('принимает кастомный label', () => {
    render(<Spinner label="Сохраняем" />);
    expect(screen.getByLabelText('Сохраняем')).toBeInTheDocument();
  });
});
