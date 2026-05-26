export const sessionQueryKeys = {
  all: ['session'] as const,
  me: () => [...sessionQueryKeys.all, 'me'] as const,
};
