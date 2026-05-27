export const featuresQueryKeys = {
  all: ['features'] as const,
  list: () => [...featuresQueryKeys.all, 'list'] as const,
};
