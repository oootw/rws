import { useSubscriptionQuery } from '@/entities/subscription';
import { PaymentsHistory } from '@/widgets/payments-history';
import { SubscriptionCard } from '@/widgets/subscription-card';
import { Card, Skeleton } from '@/shared/ui';

export function SubscriptionPage() {
  const query = useSubscriptionQuery();

  const renderSubscription = () => {
    if (query.data !== undefined) {
      return <SubscriptionCard subscription={query.data} />;
    }
    if (query.isPending) {
      return (
        <Card as="section" className="space-y-4" aria-busy="true" aria-live="polite">
          <Skeleton className="h-5 w-1/3" />
          <div className="grid grid-cols-2 gap-3">
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-4 w-2/3" />
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-4 w-3/5" />
          </div>
          <Skeleton className="h-10 w-40 rounded-xl" />
        </Card>
      );
    }
    return <Card className="text-sm text-ink-500">Не удалось получить подписку.</Card>;
  };

  return (
    <div className="space-y-6 sm:space-y-8">
      <header className="space-y-1">
        <p className="text-sm text-ink-500">Подписка и оплата</p>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Подписка</h1>
      </header>

      {renderSubscription()}

      <PaymentsHistory />
    </div>
  );
}
