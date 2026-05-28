/**
 * VAPID public key приходит base64url. PushManager.subscribe ожидает Uint8Array.
 * Эта функция — pure-конвертация без сторонних зависимостей.
 */
export const urlBase64ToUint8Array = (base64Url: string): Uint8Array => {
  const padding = '='.repeat((4 - (base64Url.length % 4)) % 4);
  const base64 = (base64Url + padding).replace(/-/g, '+').replace(/_/g, '/');
  const raw = atob(base64);
  const output = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i += 1) {
    output[i] = raw.charCodeAt(i);
  }
  return output;
};
