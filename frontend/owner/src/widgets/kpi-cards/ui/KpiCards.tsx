import { MapPin, MessageSquare, ShieldAlert, TrendingUp } from 'lucide-react';
import type { ComponentType, SVGProps } from 'react';

import type { DashboardSnapshot } from '@/entities/analytics';
import { Card, Skeleton, Sparkline } from '@/shared/ui';

type KpiCardsProps = {
  data: DashboardSnapshot | undefined;
  isLoading: boolean;
};

type Tile = {
  label: string;
  value: string;
  icon: ComponentType<SVGProps<SVGSVGElement>>;
  series?: number[];
};

const placeholderTiles: Tile[] = [
  { label: 'Сканы за 7 дней', value: '—', icon: TrendingUp },
  { label: 'Отзывы', value: '—', icon: MessageSquare },
  { label: 'Точки', value: '—', icon: MapPin },
  { label: 'Негативные', value: '—', icon: ShieldAlert },
];

const tilesFor = (data: DashboardSnapshot): Tile[] => [
  {
    label: 'Сканы за 7 дней',
    value: String(data.scans),
    icon: TrendingUp,
    series: data.daily_series.map((d) => d.scans),
  },
  {
    label: 'Отзывы',
    value: String(data.reviews),
    icon: MessageSquare,
    series: data.daily_series.map((d) => d.reviews),
  },
  {
    label: 'Точки',
    value: String(data.places_count),
    icon: MapPin,
  },
  {
    label: 'Негативные',
    value: String(data.negative),
    icon: ShieldAlert,
  },
];

export function KpiCards({ data, isLoading }: KpiCardsProps) {
  const tiles = data === undefined ? placeholderTiles : tilesFor(data);

  return (
    <section
      aria-label="Сводка"
      className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4"
      aria-busy={isLoading}
    >
      {tiles.map((tile) => (
        <Card as="article" key={tile.label}>
          <div className="flex items-center gap-2 text-ink-500">
            <tile.icon className="h-4 w-4" />
            <span className="text-xs font-medium uppercase tracking-wide">{tile.label}</span>
          </div>
          <div className="mt-3 text-3xl font-semibold tracking-tight">
            {isLoading ? <Skeleton className="h-8 w-16" /> : tile.value}
          </div>
          {tile.series !== undefined && (
            <div className="mt-2 text-accent">
              <Sparkline values={tile.series} ariaLabel={`Динамика «${tile.label}» за 7 дней`} />
            </div>
          )}
        </Card>
      ))}
    </section>
  );
}
