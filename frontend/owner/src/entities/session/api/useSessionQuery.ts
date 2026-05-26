import { useQuery } from '@tanstack/react-query';
import type { UseQueryResult } from '@tanstack/react-query';

import { httpClient, isAxiosError } from '@/shared/api';

import { sessionQueryKeys } from '../config/queryKeys';
import type { OwnerMe } from '../model/types';

type SessionEnvelope = { data: OwnerMe };

const fetchMe = async (): Promise<OwnerMe | null> => {
  try {
    const response = await httpClient.get<SessionEnvelope>('/me');
    return response.data.data;
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 401) {
      return null;
    }
    throw error;
  }
};

export const useSessionQuery = (): UseQueryResult<OwnerMe | null> =>
  useQuery({
    queryKey: sessionQueryKeys.me(),
    queryFn: fetchMe,
    staleTime: 60_000,
  });
