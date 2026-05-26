import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient } from '@/shared/api';

import { analyticsQueryKeys } from '../config/queryKeys';
import type { DashboardSnapshot } from '../model/types';

type Envelope = { data: DashboardSnapshot };

const fetchDashboard = async (): Promise<DashboardSnapshot> => {
  const response = await httpClient.get<Envelope>('/dashboard');
  return response.data.data;
};

export const useDashboardQuery = (): UseQueryResult<DashboardSnapshot> =>
  useQuery({
    queryKey: analyticsQueryKeys.dashboard(),
    queryFn: fetchDashboard,
  });
