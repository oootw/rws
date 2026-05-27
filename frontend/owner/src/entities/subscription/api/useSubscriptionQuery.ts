import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { subscriptionQueryKeys } from '../config/queryKeys';
import type { OwnerSubscription } from '../model/types';

type Envelope = { data: OwnerSubscription };

const fetchSubscription = async (): Promise<OwnerSubscription> => {
  const response = await httpClient.get<Envelope>('/subscription');
  return response.data.data;
};

export const useSubscriptionQuery = (): UseQueryResult<OwnerSubscription> =>
  useQuery({
    queryKey: subscriptionQueryKeys.current(),
    queryFn: fetchSubscription,
  });
