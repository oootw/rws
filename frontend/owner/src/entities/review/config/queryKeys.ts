import type { ReviewsFilters } from '../model/types';

export const reviewsQueryKeys = {
  all: ['reviews'] as const,
  list: (filters: ReviewsFilters) => [...reviewsQueryKeys.all, 'list', filters] as const,
};
