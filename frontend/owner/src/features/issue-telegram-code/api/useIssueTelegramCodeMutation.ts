import { useMutation } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';

export type IssuedTelegramCode = { code: string; expires_at: string };

type Envelope = { data: IssuedTelegramCode };

const issueCode = async (): Promise<IssuedTelegramCode> => {
  await ensureCsrf();
  const response = await httpClient.post<Envelope>('/profile/telegram/issue-code');
  return response.data.data;
};

export const useIssueTelegramCodeMutation = (): UseMutationResult<IssuedTelegramCode, Error, void> =>
  useMutation({ mutationFn: issueCode });
