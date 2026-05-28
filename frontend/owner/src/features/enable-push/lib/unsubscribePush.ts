import { ensureCsrf, httpClient } from '@/shared/api';

export type UnsubscribePushInput = {
  endpoint: string;
};

/**
 * Удаляет подписку и на сервере, и в браузере. Серверный DELETE — первый,
 * чтобы при сбое API остаться в согласованном состоянии (БД считает
 * подписку активной → бекенд продолжит слать; при следующем gone-ответе
 * подписка очистится). Если бы мы сначала unsubscribe'ились локально,
 * могли бы получить серверные ошибки 404 на втором клике.
 */
export const unsubscribePush = async (input: UnsubscribePushInput): Promise<void> => {
  await ensureCsrf();
  await httpClient.delete('/push/subscribe', { data: { endpoint: input.endpoint } });

  if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) return;
  const registration = await navigator.serviceWorker.ready;
  const subscription = await registration.pushManager.getSubscription();
  if (subscription !== null && subscription.endpoint === input.endpoint) {
    await subscription.unsubscribe();
  }
};
