import type { PlaceSummary } from '@/entities/place';
import { statusLabel } from '@/entities/review';
import type { ReviewStatus, ReviewsFilters } from '@/entities/review';
import { Card } from '@/shared/ui';

type ReviewsFiltersBarProps = {
  filters: ReviewsFilters;
  places: PlaceSummary[];
  onChange: (next: ReviewsFilters) => void;
};

const statuses: ReviewStatus[] = ['new', 'in_progress', 'resolved', 'archived'];

const baseSelectClass =
  'rounded-xl border border-ink-200 bg-surface px-3.5 py-2 text-sm text-ink-900 focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/15';

export function ReviewsFiltersBar({ filters, places, onChange }: ReviewsFiltersBarProps) {
  return (
    <Card className="flex flex-wrap items-center gap-3">
      <label className="flex flex-col gap-1 text-xs text-ink-500">
        Статус
        <select
          className={baseSelectClass}
          value={filters.status ?? ''}
          onChange={(event) =>
            onChange({
              ...filters,
              status: event.target.value === '' ? undefined : (event.target.value as ReviewStatus),
              page: 1,
            })
          }
        >
          <option value="">Все</option>
          {statuses.map((status) => (
            <option key={status} value={status}>
              {statusLabel(status)}
            </option>
          ))}
        </select>
      </label>

      <label className="flex flex-col gap-1 text-xs text-ink-500">
        Точка
        <select
          className={baseSelectClass}
          value={filters.placeId ?? ''}
          onChange={(event) =>
            onChange({
              ...filters,
              placeId: event.target.value === '' ? undefined : event.target.value,
              page: 1,
            })
          }
        >
          <option value="">Все</option>
          {places.map((place) => (
            <option key={place.id} value={place.id}>
              {place.title}
            </option>
          ))}
        </select>
      </label>
    </Card>
  );
}
