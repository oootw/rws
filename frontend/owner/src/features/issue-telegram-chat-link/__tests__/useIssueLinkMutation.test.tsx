import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';

vi.mock('@/shared/api', async () => {
  const actual = await vi.importActual<typeof import('@/shared/api')>('@/shared/api');
  return { ...actual, ensureCsrf: vi.fn(async () => {}) };
});

import { ensureCsrf, httpClient } from '@/shared/api';

import { useIssueLinkMutation } from '../api/useIssueLinkMutation';

function wrapper(client: QueryClient) {
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
  };
}

describe('useIssueLinkMutation', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let postSpy: any;

  beforeEach(() => {
    postSpy = vi.spyOn(httpClient, 'post');
    vi.mocked(ensureCsrf).mockClear();
  });

  afterEach(() => {
    postSpy.mockRestore();
  });

  it('зовёт ensureCsrf и POST /telegram-chats/issue-link, разворачивает конверт', async () => {
    postSpy.mockResolvedValue({
      data: {
        data: {
          deep_link: 'https://t.me/GuardReviewsBot?startgroup=abc',
          expires_at: '2026-05-29T10:00:00Z',
        },
      },
    });

    const client = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
    const { result } = renderHook(() => useIssueLinkMutation(), { wrapper: wrapper(client) });

    result.current.mutate();

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true);
    });

    expect(ensureCsrf).toHaveBeenCalledOnce();
    expect(postSpy).toHaveBeenCalledWith('/telegram-chats/issue-link');
    expect(result.current.data).toEqual({
      deep_link: 'https://t.me/GuardReviewsBot?startgroup=abc',
      expires_at: '2026-05-29T10:00:00Z',
    });
  });
});
