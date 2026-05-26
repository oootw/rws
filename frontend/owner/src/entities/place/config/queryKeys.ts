export const placesQueryKeys = {
  all: ['places'] as const,
  list: () => [...placesQueryKeys.all, 'list'] as const,
  detail: (id: string) => [...placesQueryKeys.all, 'detail', id] as const,
  chargePreview: () => [...placesQueryKeys.all, 'chargePreview'] as const,
};
