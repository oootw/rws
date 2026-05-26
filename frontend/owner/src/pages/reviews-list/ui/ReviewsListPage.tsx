import { useState } from 'react';

import { useOwnerPlacesQuery } from '@/entities/place';
import { useOwnerReviewsQuery } from '@/entities/review';
import type { ReviewsFilters } from '@/entities/review';
import { ReviewsFiltersBar, ReviewsTable } from '@/widgets/reviews-table';

const initialFilters: ReviewsFilters = { page: 1, perPage: 20 };

export function ReviewsListPage() {
  const [filters, setFilters] = useState<ReviewsFilters>(initialFilters);
  const places = useOwnerPlacesQuery();
  const reviews = useOwnerReviewsQuery(filters);

  return (
    <div className="space-y-6">
      <header className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Отзывы</h1>
        <p className="text-sm text-ink-500">Все отзывы по вашим точкам.</p>
      </header>

      <ReviewsFiltersBar
        filters={filters}
        places={places.data ?? []}
        onChange={setFilters}
      />

      <ReviewsTable
        page={reviews.data}
        isLoading={reviews.isPending || reviews.isFetching}
        onPageChange={(page) => setFilters((prev) => ({ ...prev, page }))}
      />
    </div>
  );
}
