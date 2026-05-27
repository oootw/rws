import type { ReviewStatus } from '@/entities/review';

const ALL_STATUSES: readonly ReviewStatus[] = ['new', 'in_progress', 'resolved', 'archived'];

export const availableTransitions = (current: ReviewStatus): ReviewStatus[] =>
  ALL_STATUSES.filter((status) => status !== current);
