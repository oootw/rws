import { useQuery, keepPreviousData } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { reviewsQueryKeys } from '../config/queryKeys';
import type { PaginationMeta, Review, ReviewsFilters, ReviewsPage } from '../model/types';

type ApiEnvelope = { data: Review[]; meta: PaginationMeta };

const filtersToParams = (filters: ReviewsFilters): Record<string, string | number> => {
  const params: Record<string, string | number> = {};
  if (filters.status) params.status = filters.status;
  if (filters.placeId) params.place_id = filters.placeId;
  if (filters.from) params.from = filters.from;
  if (filters.until) params.until = filters.until;
  if (filters.page) params.page = filters.page;
  if (filters.perPage) params.per_page = filters.perPage;
  return params;
};

const fetchOwnerReviews = async (filters: ReviewsFilters): Promise<ReviewsPage> => {
  const response = await httpClient.get<ApiEnvelope>('/reviews', {
    params: filtersToParams(filters),
  });
  return { items: response.data.data, meta: response.data.meta };
};

export const useOwnerReviewsQuery = (filters: ReviewsFilters): UseQueryResult<ReviewsPage> =>
  useQuery({
    queryKey: reviewsQueryKeys.list(filters),
    queryFn: () => fetchOwnerReviews(filters),
    placeholderData: keepPreviousData,
  });
