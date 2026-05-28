import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { pushSubscriptionQueryKeys } from '../config/queryKeys';
import type { PushSubscriptionDevice } from '../model/types';

type Envelope = { data: PushSubscriptionDevice[] };

export const useMyPushSubscriptionsQuery = (): UseQueryResult<PushSubscriptionDevice[]> =>
  useQuery({
    queryKey: pushSubscriptionQueryKeys.devices(),
    queryFn: async () => {
      const response = await httpClient.get<Envelope>('/push/subscriptions');
      return response.data.data;
    },
  });
