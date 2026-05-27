import type { ReactNode } from 'react';
import { Star } from 'lucide-react';

import { Card } from '@/shared/ui';

import { formatReviewDate } from '../lib/formatDate';
import { statusLabel } from '../lib/statusLabel';
import type { Review } from '../model/types';

type ReviewCardProps = {
  review: Review;
  statusSlot?: ReactNode;
};

const statusToneClass: Record<Review['status'], string> = {
  new: 'bg-danger/10 text-danger',
  in_progress: 'bg-warning/15 text-warning',
  resolved: 'bg-accent-soft text-accent',
  archived: 'bg-ink-100 text-ink-500',
};

export function ReviewCard({ review, statusSlot }: ReviewCardProps) {
  return (
    <Card as="article" className="space-y-3">
      <header className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="flex items-center gap-0.5 text-amber-500">
            {Array.from({ length: review.stars }).map((_, index) => (
              <Star key={index} className="h-4 w-4 fill-current" />
            ))}
          </span>
          <span className="text-sm font-semibold">{review.place_title}</span>
        </div>
        {statusSlot ?? (
          <span
            className={[
              'rounded-full px-2.5 py-0.5 text-xs font-medium',
              statusToneClass[review.status],
            ].join(' ')}
          >
            {statusLabel(review.status)}
          </span>
        )}
      </header>

      {review.text ? (
        <p className="text-sm text-ink-700 whitespace-pre-line">{review.text}</p>
      ) : (
        <p className="text-sm italic text-ink-400">Текст отзыва не указан.</p>
      )}

      <footer className="flex flex-wrap items-center justify-between gap-2 text-xs text-ink-500">
        <span>Контакт: {review.contact || '—'}</span>
        <time dateTime={review.created_at}>{formatReviewDate(review.created_at)}</time>
      </footer>
    </Card>
  );
}
