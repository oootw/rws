import { Button } from '@/shared/ui';

import { useInitPaymentMutation } from '../api/useInitPaymentMutation';

type ExtendSubscriptionButtonProps = {
  amountLabel: string;
};

export function ExtendSubscriptionButton({ amountLabel }: ExtendSubscriptionButtonProps) {
  const mutation = useInitPaymentMutation();
  const errorMessage =
    mutation.error instanceof Error ? mutation.error.message : null;

  return (
    <div className="space-y-2">
      <Button
        variant="primary"
        onClick={() => mutation.mutate()}
        disabled={mutation.isPending}
        aria-label="Продлить подписку"
      >
        {mutation.isPending ? 'Перенаправляем…' : `Оплатить ${amountLabel}`}
      </Button>
      {errorMessage !== null && (
        <p className="text-xs text-danger" role="alert">
          {errorMessage}
        </p>
      )}
    </div>
  );
}
