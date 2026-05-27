import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';

import { Field } from '@guard-reviews/shared/ui';

describe('Field', () => {
  it('связывает label с input через htmlFor/id', () => {
    render(
      <Field label="Email" htmlFor="email">
        <input id="email" />
      </Field>,
    );
    const label = screen.getByText('Email');
    expect(label.getAttribute('for')).toBe('email');
  });

  it('показывает error с role=alert и скрывает hint когда есть error', () => {
    render(
      <Field label="X" htmlFor="x" error="неверно" hint="подсказка">
        <input id="x" />
      </Field>,
    );
    expect(screen.getByRole('alert')).toHaveTextContent('неверно');
    expect(screen.queryByText('подсказка')).toBeNull();
  });

  it('показывает hint когда нет error', () => {
    render(
      <Field label="X" htmlFor="x" hint="введите email">
        <input id="x" />
      </Field>,
    );
    expect(screen.getByText('введите email')).toBeInTheDocument();
  });
});
