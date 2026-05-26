import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { placesQueryKeys } from '../config/queryKeys';
import type { PlaceSummary } from '../model/types';

type ListEnvelope = { data: PlaceSummary[] };

const fetchOwnerPlaces = async (): Promise<PlaceSummary[]> => {
  const response = await httpClient.get<ListEnvelope>('/places');
  return response.data.data;
};

export const useOwnerPlacesQuery = (): UseQueryResult<PlaceSummary[]> =>
  useQuery({
    queryKey: placesQueryKeys.list(),
    queryFn: fetchOwnerPlaces,
  });
