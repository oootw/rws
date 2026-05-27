import { MessageSquare } from 'lucide-react';

import { ReviewCard } from '@/entities/review';
import type { ReviewsPage } from '@/entities/review';
import { StatusSwitcher } from '@/features/change-review-status';
import { Card, EmptyState, Skeleton } from '@/shared/ui';

import { Pagination } from './Pagination';

type ReviewsTableProps = {
  page: ReviewsPage | undefined;
  isLoading: boolean;
  onPageChange: (page: number) => void;
};

const SKELETON_ROWS = 3;

export function ReviewsTable({ page, isLoading, onPageChange }: ReviewsTableProps) {
  if (page === undefined) {
    if (isLoading) {
      return (
        <div className="space-y-3" aria-busy="true" aria-live="polite">
          {Array.from({ length: SKELETON_ROWS }).map((_, index) => (
            <Card key={index} className="space-y-3">
              <Skeleton className="h-5 w-1/3" />
              <Skeleton className="h-4 w-5/6" />
              <Skeleton className="h-4 w-2/3" />
            </Card>
          ))}
        </div>
      );
    }
    return (
      <Card className="text-sm text-ink-500">Не удалось получить отзывы.</Card>
    );
  }

  if (page.items.length === 0) {
    return (
      <EmptyState
        icon={<MessageSquare className="h-8 w-8" />}
        title="Отзывов нет"
        description="Под выбранные фильтры отзывов не нашлось."
      />
    );
  }

  return (
    <div className="space-y-3" aria-busy={isLoading}>
      {page.items.map((review) => (
        <ReviewCard
          key={review.id}
          review={review}
          statusSlot={<StatusSwitcher reviewId={review.id} current={review.status} />}
        />
      ))}
      <Pagination meta={page.meta} onPageChange={onPageChange} />
    </div>
  );
}
