import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import type { OwnerMe } from '@/entities/session';
import { sessionQueryKeys } from '@/entities/session';

type ExchangeResponse = { data: OwnerMe };

const exchange = async (code: string): Promise<OwnerMe> => {
  await ensureCsrf();
  const response = await httpClient.post<ExchangeResponse>('/auth/exchange', { code });
  return response.data.data;
};

export const useExchangeCodeMutation = (): UseMutationResult<OwnerMe, Error, string> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: exchange,
    onSuccess: (owner) => {
      queryClient.setQueryData(sessionQueryKeys.me(), owner);
    },
  });
};
