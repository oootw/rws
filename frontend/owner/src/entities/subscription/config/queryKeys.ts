export const subscriptionQueryKeys = {
  all: ['subscription'] as const,
  current: () => [...subscriptionQueryKeys.all, 'current'] as const,
};
