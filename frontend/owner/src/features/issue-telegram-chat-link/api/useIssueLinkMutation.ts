import { useMutation } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import type { IssuedChatLink } from '@/entities/telegram-chat';

type Envelope = { data: IssuedChatLink };

const issueLink = async (): Promise<IssuedChatLink> => {
  await ensureCsrf();
  const response = await httpClient.post<Envelope>('/telegram-chats/issue-link');
  return response.data.data;
};

export const useIssueLinkMutation = (): UseMutationResult<IssuedChatLink, Error, void> =>
  useMutation({ mutationFn: issueLink });
