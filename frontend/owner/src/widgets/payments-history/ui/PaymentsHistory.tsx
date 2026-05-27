import { useState } from 'react';
import { Banknote } from 'lucide-react';

import { useOwnerPaymentsQuery, paymentStatusLabel } from '@/entities/payment';
import type { OwnerPayment } from '@/entities/payment';
import { formatRubles } from '@/entities/subscription';
import { Badge, Button, Card, EmptyState, Skeleton } from '@/shared/ui';
import type { BadgeTone } from '@/shared/ui';

const PER_PAGE = 10;

const statusTone: Record<OwnerPayment['status'], BadgeTone> = {
  pending: 'warning',
  success: 'accent',
  failed: 'danger',
  refunded: 'neutral',
};

const formatter = new Intl.DateTimeFormat('ru-RU', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
});

const formatCreatedAt = (iso: string): string => formatter.format(new Date(iso));

export function PaymentsHistory() {
  const [page, setPage] = useState(1);
  const query = useOwnerPaymentsQuery({ page, perPage: PER_PAGE });

  if (query.data === undefined) {
    if (query.isLoading) {
      return (
        <Card as="section" className="space-y-3" aria-busy="true" aria-live="polite">
          <Skeleton className="h-5 w-2/5" />
          <Skeleton className="h-4 w-full" />
          <Skeleton className="h-4 w-3/4" />
          <Skeleton className="h-4 w-5/6" />
        </Card>
      );
    }
    return (
      <Card className="text-sm text-ink-500">Не удалось получить историю платежей.</Card>
    );
  }

  if (query.data.items.length === 0) {
    return (
      <EmptyState
        icon={<Banknote className="h-8 w-8" />}
        title="Платежей пока нет"
        description="Когда вы оформите подписку — здесь появится история транзакций."
      />
    );
  }

  const { items, meta } = query.data;
  const canPrev = meta.page > 1;
  const canNext = meta.page < meta.last_page;

  return (
    <Card as="section" className="space-y-3">
      <h2 className="text-base font-semibold text-ink-900">История платежей</h2>
      <ul className="divide-y divide-ink-100">
        {items.map((payment) => (
          <li key={payment.id} className="flex flex-wrap items-center justify-between gap-2 py-2">
            <div>
              <p className="text-sm font-medium">{formatRubles(payment.amount)}</p>
              <p className="text-xs text-ink-500">
                {payment.tariff_title ?? 'Тариф'} · {formatCreatedAt(payment.created_at)}
              </p>
            </div>
            <Badge tone={statusTone[payment.status]}>
              {paymentStatusLabel(payment.status)}
            </Badge>
          </li>
        ))}
      </ul>
      {meta.last_page > 1 && (
        <nav className="flex items-center justify-between gap-2 pt-2" aria-label="Постраничная навигация">
          <Button variant="ghost" disabled={!canPrev} onClick={() => canPrev && setPage(page - 1)}>
            Назад
          </Button>
          <span className="text-xs text-ink-500">
            Страница {meta.page} из {meta.last_page} · {meta.total} платежей
          </span>
          <Button variant="ghost" disabled={!canNext} onClick={() => canNext && setPage(page + 1)}>
            Дальше
          </Button>
        </nav>
      )}
    </Card>
  );
}
