import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { placesQueryKeys } from '../config/queryKeys';
import type { PlaceDetail } from '../model/types';

type DetailEnvelope = { data: PlaceDetail };

const fetchPlaceDetail = async (placeId: string): Promise<PlaceDetail> => {
  const response = await httpClient.get<DetailEnvelope>(`/places/${placeId}`);
  return response.data.data;
};

export const usePlaceDetailQuery = (placeId: string | undefined): UseQueryResult<PlaceDetail> =>
  useQuery({
    queryKey: placeId ? placesQueryKeys.detail(placeId) : placesQueryKeys.detail('unknown'),
    queryFn: () => {
      if (placeId === undefined) {
        return Promise.reject(new Error('placeId is required'));
      }
      return fetchPlaceDetail(placeId);
    },
    enabled: placeId !== undefined,
  });
