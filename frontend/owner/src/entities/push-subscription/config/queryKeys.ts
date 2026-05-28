export const pushSubscriptionQueryKeys = {
  all: ['push'] as const,
  config: () => [...pushSubscriptionQueryKeys.all, 'config'] as const,
  devices: () => [...pushSubscriptionQueryKeys.all, 'devices'] as const,
};
