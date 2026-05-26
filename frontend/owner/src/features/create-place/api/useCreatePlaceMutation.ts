import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import { placesQueryKeys } from '@/entities/place';
import type { PlaceCharge, PlaceInput } from '@/entities/place';

type CreateResponse = { data: { id: string }; charge: PlaceCharge };

const createPlace = async (input: PlaceInput): Promise<CreateResponse> => {
  await ensureCsrf();
  const response = await httpClient.post<CreateResponse>('/places', input);
  return response.data;
};

export const useCreatePlaceMutation = (): UseMutationResult<CreateResponse, Error, PlaceInput> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: createPlace,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.list() });
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.chargePreview() });
    },
  });
};
