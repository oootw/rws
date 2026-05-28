import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { pushSubscriptionQueryKeys } from '../config/queryKeys';
import type { PushConfig } from '../model/types';

type Envelope = { data: PushConfig };

const FIVE_MINUTES = 5 * 60_000;

const fetchPushConfig = async (): Promise<PushConfig> => {
  const response = await httpClient.get<Envelope>('/push/config');
  return response.data.data;
};

export const usePushConfigQuery = (): UseQueryResult<PushConfig> =>
  useQuery({
    queryKey: pushSubscriptionQueryKeys.config(),
    queryFn: fetchPushConfig,
    staleTime: FIVE_MINUTES,
  });
