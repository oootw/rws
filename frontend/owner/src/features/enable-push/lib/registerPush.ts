import { ensureCsrf, httpClient } from '@/shared/api';

import { urlBase64ToUint8Array } from './urlBase64ToUint8Array';

export type RegisterPushInput = {
  vapidPublicKey: string;
  userAgent: string | null;
};

const PUSH_PERMISSION_DENIED = 'PushPermissionDenied' as const;

export type PushPermissionDeniedError = Error & { name: typeof PUSH_PERMISSION_DENIED };

export const isPushPermissionDenied = (error: unknown): error is PushPermissionDeniedError =>
  error instanceof Error && error.name === PUSH_PERMISSION_DENIED;

const makePushPermissionDenied = (): PushPermissionDeniedError => {
  const error = new Error('Push notifications permission was denied.') as PushPermissionDeniedError;
  error.name = PUSH_PERMISSION_DENIED;
  return error;
};

/**
 * Pure-функция оркестрации:
 *  1) Notification.requestPermission()  — должна вызываться по клику.
 *  2) serviceWorker.ready → pushManager.subscribe.
 *  3) POST endpoint + ключи на бэк.
 *
 * Sonner / React Query / state — НЕ здесь, чтобы можно было тестировать
 * чистыми моками браузерных API.
 */
export const registerPush = async (input: RegisterPushInput): Promise<PushSubscription> => {
  const permission = await Notification.requestPermission();
  if (permission !== 'granted') {
    throw makePushPermissionDenied();
  }

  const registration = await navigator.serviceWorker.ready;
  // applicationServerKey ожидает BufferSource — Uint8Array из lib.dom.d.ts
  // имеет шире union (ArrayBufferLike), приводим к ArrayBuffer-ограниченному.
  const applicationServerKey = urlBase64ToUint8Array(input.vapidPublicKey) as BufferSource;
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey,
  });

  const json = subscription.toJSON();
  await ensureCsrf();
  await httpClient.post('/push/subscribe', {
    endpoint: json.endpoint,
    keys: json.keys,
    user_agent: input.userAgent,
  });

  return subscription;
};
