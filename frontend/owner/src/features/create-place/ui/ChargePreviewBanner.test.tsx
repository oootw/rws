import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';

import { ChargePreviewBanner } from './ChargePreviewBanner';

describe('ChargePreviewBanner', () => {
  it('не рендерится при нулевом monthly_delta', () => {
    const { container } = render(
      <ChargePreviewBanner
        charge={{
          prorata_amount: 0,
          days_left: 0,
          monthly_delta: 0,
          requires_payment: false,
        }}
      />,
    );
    expect(container).toBeEmptyDOMElement();
  });

  it('показывает прорату и предупреждение о следующем месяце при requires_payment', () => {
    render(
      <ChargePreviewBanner
        charge={{
          prorata_amount: 14_000,
          days_left: 14,
          monthly_delta: 30_000,
          requires_payment: true,
        }}
      />,
    );
    expect(screen.getByText(/доплата/i)).toBeInTheDocument();
    expect(screen.getByText(/со следующего платежа/i)).toBeInTheDocument();
  });

  it('показывает «без доплаты» когда requires_payment=false но monthly_delta>0', () => {
    render(
      <ChargePreviewBanner
        charge={{
          prorata_amount: 0,
          days_left: 0,
          monthly_delta: 30_000,
          requires_payment: false,
        }}
      />,
    );
    expect(screen.getByText(/без доплаты/i)).toBeInTheDocument();
  });
});
