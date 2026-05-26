import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';

import { httpClient } from '@/shared/api';

import { AppRouter } from './AppRouter';

function renderAt(path: string) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  });

  return render(
    <QueryClientProvider client={client}>
      <MemoryRouter initialEntries={[path]}>
        <AppRouter />
      </MemoryRouter>
    </QueryClientProvider>,
  );
}

describe('App routing', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let getSpy: any;

  beforeEach(() => {
    getSpy = vi.spyOn(httpClient, 'get');
  });

  afterEach(() => {
    getSpy.mockRestore();
  });

  it('редиректит на /login при отсутствии сессии', async () => {
    getSpy.mockRejectedValue({
      isAxiosError: true,
      response: { status: 401 },
    });

    renderAt('/');

    await waitFor(() => {
      expect(
        screen.getByRole('heading', { level: 1, name: 'Вход в кабинет' }),
      ).toBeInTheDocument();
    });
  });

  it('рендерит дашборд при наличии сессии', async () => {
    getSpy.mockResolvedValue({
      data: {
        data: {
          id: 'u-1',
          name: 'Иван',
          email: 'owner@example.com',
          subdomain: 'cafe',
          telegram_connected: true,
        },
      },
    });

    renderAt('/');

    await waitFor(() => {
      expect(
        screen.getByRole('heading', { level: 1, name: 'Кабинет владельца' }),
      ).toBeInTheDocument();
    });
  });

  it('рендерит login-страницу на /login', () => {
    renderAt('/login');
    expect(
      screen.getByRole('heading', { level: 1, name: 'Вход в кабинет' }),
    ).toBeInTheDocument();
  });

  it('рендерит 404 на неизвестных маршрутах', () => {
    renderAt('/this/route/does/not/exist');
    expect(screen.getByText('Страница не найдена')).toBeInTheDocument();
  });
});
