import { ReviewCard } from '@/entities/review';
import type { ReviewsPage } from '@/entities/review';
import { Card } from '@/shared/ui';

import { Pagination } from './Pagination';

type ReviewsTableProps = {
  page: ReviewsPage | undefined;
  isLoading: boolean;
  onPageChange: (page: number) => void;
};

export function ReviewsTable({ page, isLoading, onPageChange }: ReviewsTableProps) {
  if (page === undefined) {
    return (
      <Card className="text-sm text-ink-500" aria-busy={isLoading}>
        {isLoading ? 'Загружаем отзывы…' : 'Нет данных.'}
      </Card>
    );
  }

  if (page.items.length === 0) {
    return (
      <Card className="text-sm text-ink-500">
        Под выбранные фильтры отзывов не нашлось.
      </Card>
    );
  }

  return (
    <div className="space-y-3" aria-busy={isLoading}>
      {page.items.map((review) => (
        <ReviewCard key={review.id} review={review} />
      ))}
      <Pagination meta={page.meta} onPageChange={onPageChange} />
    </div>
  );
}
