import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

vi.mock('@/shared/api', async () => {
  const actual = await vi.importActual<typeof import('@/shared/api')>('@/shared/api');
  return { ...actual, ensureCsrf: vi.fn(async () => {}) };
});

import { httpClient } from '@/shared/api';
import type { OwnerSubscription } from '@/entities/subscription';

import { SubscriptionCard } from './SubscriptionCard';

const baseSubscription: OwnerSubscription = {
  tariff_id: 't-1',
  tariff_title: 'Pro',
  ends_at: '2026-06-15T00:00:00Z',
  days_left: 14,
  is_active: true,
  places_used: 2,
  places_limit: 5,
  next_charge_amount: 129_000,
};

function renderWith(client: QueryClient, ui: React.ReactNode) {
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>);
}

describe('SubscriptionCard', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let postSpy: any;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let hrefSpy: any;
  let originalLocation: Location;

  beforeEach(() => {
    postSpy = vi.spyOn(httpClient, 'post').mockResolvedValue({
      data: { data: { payment_url: 'https://pay.test/abc' } },
    });
    originalLocation = window.location;
    hrefSpy = vi.fn();
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { ...originalLocation, set href(value: string) { hrefSpy(value); } },
    });
  });

  afterEach(() => {
    postSpy.mockRestore();
    Object.defineProperty(window, 'location', { configurable: true, value: originalLocation });
  });

  it('показывает тариф, лимит точек и сумму', () => {
    const client = new QueryClient();
    renderWith(client, <SubscriptionCard subscription={baseSubscription} />);

    expect(screen.getByText('Pro')).toBeInTheDocument();
    expect(screen.getByText('Активна')).toBeInTheDocument();
    expect(screen.getByText('2 из 5')).toBeInTheDocument();
    expect(screen.getAllByText(/1\s?290 ₽/).length).toBeGreaterThan(0);
  });

  it('помечает «Истекла» когда is_active=false', () => {
    const client = new QueryClient();
    renderWith(client, <SubscriptionCard subscription={{ ...baseSubscription, is_active: false }} />);
    expect(screen.getByText('Истекла')).toBeInTheDocument();
  });

  it('инициирует оплату и редиректит на payment_url', async () => {
    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    renderWith(client, <SubscriptionCard subscription={baseSubscription} />);

    fireEvent.click(screen.getByRole('button', { name: 'Продлить подписку' }));

    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith('/subscription/init-payment');
      expect(hrefSpy).toHaveBeenCalledWith('https://pay.test/abc');
    });
  });
});
