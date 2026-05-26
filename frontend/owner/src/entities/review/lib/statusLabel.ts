import type { ReviewStatus } from '../model/types';

const labels: Record<ReviewStatus, string> = {
  new: 'Новый',
  in_progress: 'В работе',
  resolved: 'Решён',
  archived: 'Архив',
};

export const statusLabel = (status: ReviewStatus): string => labels[status];
