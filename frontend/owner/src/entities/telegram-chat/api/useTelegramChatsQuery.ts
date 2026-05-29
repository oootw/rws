import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { telegramChatsQueryKeys } from '../config/queryKeys';
import type { TelegramChat } from '../model/types';

type Envelope = { data: TelegramChat[] };

export const useTelegramChatsQuery = (): UseQueryResult<TelegramChat[]> =>
  useQuery({
    queryKey: telegramChatsQueryKeys.list(),
    queryFn: async () => {
      const response = await httpClient.get<Envelope>('/telegram-chats');
      return response.data.data;
    },
  });
