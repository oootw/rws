import { useQuery, keepPreviousData } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { paymentsQueryKeys } from '../config/queryKeys';
import type { OwnerPayment, OwnerPaymentsFilters, OwnerPaymentsPage, PaginationMeta } from '../model/types';

type Envelope = { data: OwnerPayment[]; meta: PaginationMeta };

const filtersToParams = (filters: OwnerPaymentsFilters): Record<string, number> => {
  const params: Record<string, number> = {};
  if (filters.page) params.page = filters.page;
  if (filters.perPage) params.per_page = filters.perPage;
  return params;
};

const fetchOwnerPayments = async (filters: OwnerPaymentsFilters): Promise<OwnerPaymentsPage> => {
  const response = await httpClient.get<Envelope>('/payments', { params: filtersToParams(filters) });
  return { items: response.data.data, meta: response.data.meta };
};

export const useOwnerPaymentsQuery = (
  filters: OwnerPaymentsFilters,
): UseQueryResult<OwnerPaymentsPage> =>
  useQuery({
    queryKey: paymentsQueryKeys.list(filters),
    queryFn: () => fetchOwnerPayments(filters),
    placeholderData: keepPreviousData,
  });
