import { Link } from 'react-router-dom';
import { Sparkles } from 'lucide-react';

import { Button, Card, Stack } from '@/shared/ui';

type UpsellCardProps = {
  title?: string;
  description?: string;
  ctaLabel?: string;
};

export function UpsellCard({
  title = 'Доступно в платных тарифах',
  description = 'Эта возможность включена не на вашем тарифе. Обновите подписку, чтобы открыть её.',
  ctaLabel = 'Посмотреть тарифы',
}: UpsellCardProps) {
  return (
    <Card as="section" className="border border-dashed border-accent/40 bg-accent-soft/40">
      <Stack gap={3}>
        <Stack direction="row" gap={2} align="center">
          <Sparkles className="h-5 w-5 text-accent" aria-hidden="true" />
          <h3 className="text-base font-semibold text-ink-900">{title}</h3>
        </Stack>
        <p className="text-sm text-ink-700">{description}</p>
        <div>
          <Link to="/subscription">
            <Button variant="primary">{ctaLabel}</Button>
          </Link>
        </div>
      </Stack>
    </Card>
  );
}
