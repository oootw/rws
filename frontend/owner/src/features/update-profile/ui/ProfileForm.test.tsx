import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

vi.mock('@/shared/api', async () => {
  const actual = await vi.importActual<typeof import('@/shared/api')>('@/shared/api');
  return { ...actual, ensureCsrf: vi.fn(async () => {}) };
});

vi.mock('sonner', () => ({
  toast: { success: vi.fn(), error: vi.fn() },
}));

import { httpClient } from '@/shared/api';
import { sessionQueryKeys } from '@/entities/session';
import type { OwnerMe } from '@/entities/session';
import { toast } from 'sonner';

import { ProfileForm } from './ProfileForm';

const initial = { name: 'Иван', email: 'old@example.com', subdomain: 'cafe' };

function renderWith(client: QueryClient, ui: React.ReactNode) {
  return render(<QueryClientProvider client={client}>{ui}</QueryClientProvider>);
}

describe('ProfileForm', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let patchSpy: any;

  beforeEach(() => {
    patchSpy = vi.spyOn(httpClient, 'patch');
    vi.mocked(toast.success).mockClear();
    vi.mocked(toast.error).mockClear();
  });

  afterEach(() => {
    patchSpy.mockRestore();
  });

  it('шлёт PATCH и обновляет кеш сессии', async () => {
    const updated: OwnerMe = {
      id: 'u-1',
      name: 'Новый',
      email: 'new@example.com',
      subdomain: 'cafe',
      telegram_connected: true,
    };
    patchSpy.mockResolvedValue({ data: { data: updated } });

    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    renderWith(client, <ProfileForm initial={initial} />);

    fireEvent.change(screen.getByLabelText('Имя'), { target: { value: 'Новый' } });
    fireEvent.change(screen.getByLabelText('Email'), { target: { value: 'new@example.com' } });
    fireEvent.click(screen.getByRole('button', { name: 'Сохранить' }));

    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith('/profile', {
        name: 'Новый',
        email: 'new@example.com',
        subdomain: 'cafe',
      });
      expect(client.getQueryData<OwnerMe>(sessionQueryKeys.me())).toEqual(updated);
      expect(toast.success).toHaveBeenCalledWith('Профиль обновлён.');
    });
  });

  it('показывает field-уровневые ошибки 422', async () => {
    patchSpy.mockRejectedValue({
      isAxiosError: true,
      response: {
        status: 422,
        data: { message: 'Адрес «taken» уже занят.', errors: { subdomain: ['Адрес «taken» уже занят.'] } },
      },
    });

    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    renderWith(client, <ProfileForm initial={initial} />);

    fireEvent.change(screen.getByLabelText('Адрес (поддомен)'), { target: { value: 'taken' } });
    fireEvent.click(screen.getByRole('button', { name: 'Сохранить' }));

    await waitFor(() => {
      expect(screen.getByText('Адрес «taken» уже занят.')).toBeInTheDocument();
    });
    expect(toast.error).not.toHaveBeenCalled();
  });

  it('предупреждает о смене поддомена и показывает success с новым адресом', async () => {
    patchSpy.mockResolvedValue({
      data: {
        data: {
          id: 'u-1',
          name: 'Иван',
          email: 'old@example.com',
          subdomain: 'new-cafe',
          telegram_connected: true,
        },
      },
    });

    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    renderWith(client, <ProfileForm initial={initial} />);

    fireEvent.change(screen.getByLabelText('Адрес (поддомен)'), { target: { value: 'new-cafe' } });
    expect(screen.getByText(/перелогиниться на новом поддомене/i)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: 'Сохранить' }));

    await waitFor(() => {
      expect(toast.success).toHaveBeenCalledWith(
        expect.stringContaining('new-cafe.otziv.space'),
      );
    });
  });
});
