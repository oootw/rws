import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient } from '@/shared/api';
import { placesQueryKeys } from '@/entities/place';
import type { PlaceDetail, PlaceSummary } from '@/entities/place';

type ToggleArgs = { placeId: string; active: boolean };

const toggle = async ({ placeId, active }: ToggleArgs): Promise<void> => {
  await ensureCsrf();
  await httpClient.post(`/places/${placeId}/toggle`, { active });
};

export const useTogglePlaceActivationMutation = (): UseMutationResult<void, Error, ToggleArgs> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: toggle,
    onMutate: ({ placeId, active }) => {
      const previousDetail = queryClient.getQueryData<PlaceDetail>(placesQueryKeys.detail(placeId));
      const previousList = queryClient.getQueryData<PlaceSummary[]>(placesQueryKeys.list());

      if (previousDetail !== undefined) {
        queryClient.setQueryData<PlaceDetail>(placesQueryKeys.detail(placeId), {
          ...previousDetail,
          is_active: active,
        });
      }
      if (previousList !== undefined) {
        queryClient.setQueryData<PlaceSummary[]>(
          placesQueryKeys.list(),
          previousList.map((place) =>
            place.id === placeId ? { ...place, is_active: active } : place,
          ),
        );
      }

      return { previousDetail, previousList };
    },
    onError: (_error, { placeId }, context) => {
      if (context?.previousDetail !== undefined) {
        queryClient.setQueryData(placesQueryKeys.detail(placeId), context.previousDetail);
      }
      if (context?.previousList !== undefined) {
        queryClient.setQueryData(placesQueryKeys.list(), context.previousList);
      }
    },
    onSettled: (_data, _error, { placeId }) => {
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.detail(placeId) });
      queryClient.invalidateQueries({ queryKey: placesQueryKeys.list() });
    },
  });
};
