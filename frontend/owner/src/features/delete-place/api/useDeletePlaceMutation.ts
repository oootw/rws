import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import { placesQueryKeys } from '@/entities/place';

const deletePlace = async (placeId: string): Promise<void> => {
  await ensureCsrf();
  await httpClient.delete(`/places/${placeId}`);
};

export const useDeletePlaceMutation = (): UseMutationResult<void, Error, string> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: deletePlace,
    onSuccess: (_data, placeId) => {
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.list() });
      queryClient.removeQueries({ queryKey: placesQueryKeys.detail(placeId) });
    },
  });
};
