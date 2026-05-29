import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';

vi.mock('@/shared/api', async () => {
  const actual = await vi.importActual<typeof import('@/shared/api')>('@/shared/api');
  return { ...actual, ensureCsrf: vi.fn(async () => {}) };
});

import { ensureCsrf, httpClient } from '@/shared/api';
import { telegramChatsQueryKeys } from '@/entities/telegram-chat';

import { useUnlinkMutation } from '../api/useUnlinkMutation';

function wrapper(client: QueryClient) {
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
  };
}

describe('useUnlinkMutation', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let deleteSpy: any;

  beforeEach(() => {
    deleteSpy = vi.spyOn(httpClient, 'delete');
    vi.mocked(ensureCsrf).mockClear();
  });

  afterEach(() => {
    deleteSpy.mockRestore();
  });

  it('DELETE /telegram-chats/{id} и инвалидирует список', async () => {
    deleteSpy.mockResolvedValue({ data: {} });

    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    const invalidateSpy = vi.spyOn(client, 'invalidateQueries');

    const { result } = renderHook(() => useUnlinkMutation(), { wrapper: wrapper(client) });

    result.current.mutate('chat-row-1');

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(ensureCsrf).toHaveBeenCalledOnce();
    expect(deleteSpy).toHaveBeenCalledWith('/telegram-chats/chat-row-1');
    expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: telegramChatsQueryKeys.list() });
  });
});
