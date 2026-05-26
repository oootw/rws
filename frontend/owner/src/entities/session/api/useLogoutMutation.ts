import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';

import { sessionQueryKeys } from '../config/queryKeys';

const logout = async (): Promise<void> => {
  await ensureCsrf();
  await httpClient.post('/auth/logout');
};

export const useLogoutMutation = (): UseMutationResult<void, Error, void> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: logout,
    onSuccess: () => {
      queryClient.setQueryData(sessionQueryKeys.me(), null);
      queryClient.invalidateQueries({ queryKey: sessionQueryKeys.all });
    },
  });
};
