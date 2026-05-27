import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

vi.mock('@/shared/api', async () => {
  const actual = await vi.importActual<typeof import('@/shared/api')>('@/shared/api');
  return { ...actual, ensureCsrf: vi.fn(async () => {}) };
});

import { httpClient } from '@/shared/api';

import { TelegramCodeCard } from './TelegramCodeCard';

function renderWith(client: QueryClient, ui: React.ReactNode) {
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>);
}

describe('TelegramCodeCard', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let postSpy: any;

  beforeEach(() => {
    postSpy = vi.spyOn(httpClient, 'post');
  });

  afterEach(() => {
    postSpy.mockRestore();
  });

  it('показывает подсказку для отвязанного Telegram', () => {
    const client = new QueryClient();
    renderWith(client, <TelegramCodeCard isConnected={false} />);
    expect(screen.getByText(/Аккаунт не привязан/i)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /Получить код/i })).toBeNull();
  });

  it('запрашивает и показывает выданный код', async () => {
    postSpy.mockResolvedValue({
      data: { data: { code: '123456', expires_at: '2026-05-27T16:30:00Z' } },
    });

    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    renderWith(client, <TelegramCodeCard isConnected={true} />);

    fireEvent.click(screen.getByRole('button', { name: 'Получить код' }));

    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith('/profile/telegram/issue-code');
      expect(screen.getByText('123456')).toBeInTheDocument();
    });
  });

  it('показывает ошибку при отказе сервера', async () => {
    postSpy.mockRejectedValue(new Error('boom'));
    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    renderWith(client, <TelegramCodeCard isConnected={true} />);

    fireEvent.click(screen.getByRole('button', { name: 'Получить код' }));

    await waitFor(() => {
      expect(screen.getByRole('alert')).toHaveTextContent(/не удалось получить код/i);
    });
  });
});
