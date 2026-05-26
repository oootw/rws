import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import { placesQueryKeys } from '@/entities/place';
import type { PlaceInput } from '@/entities/place';

type UpdateArgs = { placeId: string; input: PlaceInput };
type UpdateResponse = { data: { id: string } };

const updatePlace = async ({ placeId, input }: UpdateArgs): Promise<UpdateResponse> => {
  await ensureCsrf();
  const response = await httpClient.patch<UpdateResponse>(`/places/${placeId}`, input);
  return response.data;
};

export const useUpdatePlaceMutation = (): UseMutationResult<UpdateResponse, Error, UpdateArgs> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: updatePlace,
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.list() });
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.detail(variables.placeId) });
    },
  });
};
