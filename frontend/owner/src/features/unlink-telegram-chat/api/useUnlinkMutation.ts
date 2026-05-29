import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import { telegramChatsQueryKeys } from '@/entities/telegram-chat';

const unlinkChat = async (chatRowId: string): Promise<void> => {
  await ensureCsrf();
  await httpClient.delete(`/telegram-chats/${chatRowId}`);
};

export const useUnlinkMutation = (): UseMutationResult<void, Error, string> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: unlinkChat,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: telegramChatsQueryKeys.list() });
    },
  });
};
