import { MapPin } from 'lucide-react';

import { Card } from '@/shared/ui';

import type { PlaceSummary } from '../model/types';

type PlaceCardProps = {
  place: PlaceSummary;
  to?: string;
  onClick?: () => void;
};

export function PlaceCard({ place, onClick }: PlaceCardProps) {
  return (
    <Card
      as="article"
      className={`flex items-center justify-between gap-3 ${onClick ? 'cursor-pointer hover:shadow-lift transition' : ''}`}
      onClick={onClick}
    >
      <div className="flex items-center gap-3">
        <span
          aria-hidden
          className="flex h-10 w-10 items-center justify-center rounded-xl bg-ink-100 text-ink-500"
        >
          <MapPin className="h-5 w-5" />
        </span>
        <div>
          <div className="text-base font-semibold">{place.title}</div>
          <div className="text-xs text-ink-500">
            Площадок: {place.platforms_count}
          </div>
        </div>
      </div>
      <span
        className={[
          'rounded-full px-2.5 py-0.5 text-xs font-medium',
          place.is_active
            ? 'bg-accent-soft text-accent'
            : 'bg-ink-100 text-ink-500',
        ].join(' ')}
      >
        {place.is_active ? 'активна' : 'выключена'}
      </span>
    </Card>
  );
}
