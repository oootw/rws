import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { featuresQueryKeys } from '../config/queryKeys';
import type { Feature } from '../model/types';

type Envelope = { data: { features: Feature[] } };

const FIVE_MINUTES = 5 * 60_000;

const fetchFeatures = async (): Promise<Set<Feature>> => {
  const response = await httpClient.get<Envelope>('/features');
  return new Set(response.data.data.features);
};

export const useFeaturesQuery = (): UseQueryResult<Set<Feature>> =>
  useQuery({
    queryKey: featuresQueryKeys.list(),
    queryFn: fetchFeatures,
    staleTime: FIVE_MINUTES,
  });
