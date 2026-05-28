import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { pushSubscriptionQueryKeys } from '@/entities/push-subscription';

import { registerPush } from '../lib/registerPush';
import type { RegisterPushInput } from '../lib/registerPush';

export const useEnablePushMutation = (): UseMutationResult<PushSubscription, Error, RegisterPushInput> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: registerPush,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: pushSubscriptionQueryKeys.devices() });
    },
  });
};
