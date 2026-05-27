import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

vi.mock('@/shared/api', async () => {
  const actual = await vi.importActual<typeof import('@/shared/api')>('@/shared/api');
  return { ...actual, ensureCsrf: vi.fn(async () => {}) };
});

import { httpClient } from '@/shared/api';
import { reviewsQueryKeys } from '@/entities/review';
import type { ReviewsPage } from '@/entities/review';

import { StatusSwitcher } from './StatusSwitcher';

function renderWith(client: QueryClient, ui: React.ReactNode) {
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>);
}

const samplePage = (status: 'new' | 'in_progress' | 'resolved' | 'archived'): ReviewsPage => ({
  items: [
    {
      id: 'r-1',
      place_id: 'p-1',
      place_title: 'Кафе',
      stars: 2,
      status,
      contact: '+7',
      text: 'плохо',
      created_at: '2026-05-26T10:00:00Z',
    },
  ],
  meta: { total: 1, page: 1, per_page: 20, last_page: 1 },
});

describe('StatusSwitcher', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let patchSpy: any;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let getSpy: any;

  beforeEach(() => {
    patchSpy = vi.spyOn(httpClient, 'patch').mockResolvedValue({ data: {} });
    getSpy = vi.spyOn(httpClient, 'get').mockResolvedValue({ data: {} });
  });

  afterEach(() => {
    patchSpy.mockRestore();
    getSpy.mockRestore();
  });

  it('показывает текущий статус и список переходов', () => {
    const client = new QueryClient();
    renderWith(client, <StatusSwitcher reviewId="r-1" current="new" />);

    fireEvent.click(screen.getByRole('button', { name: /изменить статус/i }));

    expect(screen.getByRole('option', { name: 'В работе' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Решён' })).toBeInTheDocument();
    expect(screen.getByRole('option', { name: 'Архив' })).toBeInTheDocument();
    expect(screen.queryByRole('option', { name: 'Новый' })).toBeNull();
  });

  it('шлёт PATCH и оптимистично обновляет кеш отзывов', async () => {
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    const filters = { page: 1, perPage: 20 };
    client.setQueryData(reviewsQueryKeys.list(filters), samplePage('new'));

    renderWith(client, <StatusSwitcher reviewId="r-1" current="new" />);

    fireEvent.click(screen.getByRole('button', { name: /изменить статус/i }));
    fireEvent.click(screen.getByRole('option', { name: 'Решён' }));

    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith('/reviews/r-1/status', { status: 'resolved' });
    });

    const cached = client.getQueryData<ReviewsPage>(reviewsQueryKeys.list(filters));
    expect(cached?.items[0].status).toBe('resolved');
  });

  it('откатывает кеш на ошибке', async () => {
    patchSpy.mockRejectedValueOnce(new Error('boom'));

    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    const filters = { page: 1, perPage: 20 };
    client.setQueryData(reviewsQueryKeys.list(filters), samplePage('new'));

    renderWith(client, <StatusSwitcher reviewId="r-1" current="new" />);

    fireEvent.click(screen.getByRole('button', { name: /изменить статус/i }));
    fireEvent.click(screen.getByRole('option', { name: 'Решён' }));

    await waitFor(() => {
      const cached = client.getQueryData<ReviewsPage>(reviewsQueryKeys.list(filters));
      expect(cached?.items[0].status).toBe('new');
    });
  });
});
