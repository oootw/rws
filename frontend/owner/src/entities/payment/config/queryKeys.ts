import type { OwnerPaymentsFilters } from '../model/types';

export const paymentsQueryKeys = {
  all: ['payments'] as const,
  list: (filters: OwnerPaymentsFilters) => [...paymentsQueryKeys.all, 'list', filters] as const,
};
