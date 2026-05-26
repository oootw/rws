import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { placesQueryKeys } from '../config/queryKeys';
import type { PlaceCharge } from '../model/types';

type Envelope = { data: PlaceCharge };

const fetchPreview = async (): Promise<PlaceCharge> => {
  const response = await httpClient.get<Envelope>('/places/charge-preview');
  return response.data.data;
};

export const usePlaceChargePreviewQuery = (enabled = true): UseQueryResult<PlaceCharge> =>
  useQuery({
    queryKey: placesQueryKeys.chargePreview(),
    queryFn: fetchPreview,
    enabled,
  });
