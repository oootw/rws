import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';

import { KpiCards } from './KpiCards';
import type { DashboardSnapshot } from '@/entities/analytics';

const snapshot: DashboardSnapshot = {
  scans: 42,
  reviews: 7,
  negative: 2,
  redirects: 15,
  places_count: 3,
  daily_series: Array.from({ length: 7 }, (_, i) => ({
    date: `2026-05-${20 + i}`,
    scans: i,
    reviews: 0,
  })),
};

describe('KpiCards', () => {
  it('рендерит плейсхолдеры без данных', () => {
    render(<KpiCards data={undefined} isLoading={true} />);
    expect(screen.getByLabelText('Сводка')).toHaveAttribute('aria-busy', 'true');
  });

  it('рендерит KPI и спарклайн при наличии данных', () => {
    render(<KpiCards data={snapshot} isLoading={false} />);
    expect(screen.getByText('42')).toBeInTheDocument();
    expect(screen.getByText('7')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
    expect(screen.getByLabelText(/Динамика «Сканы за 7 дней»/)).toBeInTheDocument();
  });
});
