export const analyticsQueryKeys = {
  all: ['analytics'] as const,
  dashboard: () => [...analyticsQueryKeys.all, 'dashboard'] as const,
};
