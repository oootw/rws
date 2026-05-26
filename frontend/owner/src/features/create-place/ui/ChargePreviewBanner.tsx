import { Info } from 'lucide-react';

import type { PlaceCharge } from '@/entities/place';
import { Card } from '@/shared/ui';
import { formatKopecks } from '@/shared/lib';

type ChargePreviewBannerProps = {
  charge: PlaceCharge | undefined;
};

/**
 * Информер «сколько будет стоить добавление ещё одной точки».
 * Скрыт если данные не загружены или extra_place_price=0.
 */
export function ChargePreviewBanner({ charge }: ChargePreviewBannerProps) {
  if (charge === undefined || charge.monthly_delta === 0) {
    return null;
  }

  if (charge.requires_payment) {
    return (
      <Card className="flex items-start gap-3 bg-accent-soft/50">
        <Info className="mt-0.5 h-5 w-5 text-accent" />
        <div className="space-y-1 text-sm text-ink-700">
          <p>
            Сейчас доплата&nbsp;<b>{formatKopecks(charge.prorata_amount)}</b> за оставшиеся{' '}
            {charge.days_left} дн. подписки.
          </p>
          <p className="text-ink-500">
            Со следующего платежа за подписку добавится{' '}
            <b>{formatKopecks(charge.monthly_delta)}</b> к ежемесячной сумме.
          </p>
        </div>
      </Card>
    );
  }

  return (
    <Card className="flex items-start gap-3 bg-accent-soft/30">
      <Info className="mt-0.5 h-5 w-5 text-accent" />
      <div className="text-sm text-ink-700">
        Точка добавится без доплаты прямо сейчас. Со следующего платежа за подписку
        к ежемесячной сумме добавится <b>{formatKopecks(charge.monthly_delta)}</b>.
      </div>
    </Card>
  );
}
