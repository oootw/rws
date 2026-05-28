import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { pushSubscriptionQueryKeys } from '@/entities/push-subscription';

import { unsubscribePush } from '../lib/unsubscribePush';
import type { UnsubscribePushInput } from '../lib/unsubscribePush';

export const useDisablePushMutation = (): UseMutationResult<void, Error, UnsubscribePushInput> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: unsubscribePush,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: pushSubscriptionQueryKeys.devices() });
    },
  });
};
