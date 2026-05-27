import { formatEndsAt, formatRubles } from '@/entities/subscription';
import type { OwnerSubscription } from '@/entities/subscription';
import { ExtendSubscriptionButton } from '@/features/extend-subscription';
import { Card } from '@/shared/ui';

type SubscriptionCardProps = {
  subscription: OwnerSubscription;
};

const statusBadge = (isActive: boolean): { label: string; tone: string } =>
  isActive
    ? { label: 'Активна', tone: 'bg-accent-soft text-accent' }
    : { label: 'Истекла', tone: 'bg-danger/10 text-danger' };

export function SubscriptionCard({ subscription }: SubscriptionCardProps) {
  const badge = statusBadge(subscription.is_active);
  const placesLabel =
    subscription.places_limit === null
      ? `${subscription.places_used}`
      : `${subscription.places_used} из ${subscription.places_limit}`;

  return (
    <Card as="section" className="space-y-4">
      <header className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <h2 className="text-base font-semibold text-ink-900">
            {subscription.tariff_title ?? 'Без тарифа'}
          </h2>
          <p className="text-xs text-ink-500">Текущий тариф</p>
        </div>
        <span
          className={['rounded-full px-2.5 py-0.5 text-xs font-medium', badge.tone].join(' ')}
        >
          {badge.label}
        </span>
      </header>

      <dl className="grid grid-cols-2 gap-3 text-sm">
        <div>
          <dt className="text-ink-500">Действует до</dt>
          <dd className="font-medium">{formatEndsAt(subscription.ends_at)}</dd>
        </div>
        <div>
          <dt className="text-ink-500">Осталось дней</dt>
          <dd className="font-medium">{subscription.days_left}</dd>
        </div>
        <div>
          <dt className="text-ink-500">Точки</dt>
          <dd className="font-medium">{placesLabel}</dd>
        </div>
        <div>
          <dt className="text-ink-500">Следующий платёж</dt>
          <dd className="font-medium">{formatRubles(subscription.next_charge_amount)}</dd>
        </div>
      </dl>

      <ExtendSubscriptionButton amountLabel={formatRubles(subscription.next_charge_amount)} />
    </Card>
  );
}
