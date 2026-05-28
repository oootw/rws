/// <reference lib="webworker" />

/**
 * Owner-панель Service Worker.
 *
 * Зачем свой SW вместо generateSW: нужно перехватывать `push` и
 * `notificationclick` для Web Push (фаза B). Workbox-precaching и runtime-кеши
 * остались — собираем их здесь напрямую через workbox-* пакеты.
 *
 * Scope: `/owner/` (см. vite.config.ts → manifest.scope и base).
 */

import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { NetworkFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { clientsClaim } from 'workbox-core';

declare const self: ServiceWorkerGlobalScope & {
  __WB_MANIFEST: Array<{ url: string; revision: string | null }>;
};

// При autoUpdate plugin'а делаем skipWaiting вручную, иначе пуш может
// прийти на старый SW сразу после деплоя, и notificationclick откроет
// устаревший shell.
self.skipWaiting();
clientsClaim();

precacheAndRoute(self.__WB_MANIFEST);

// Сессия: NetworkFirst, чтобы быстро узнать о logout/смене slug.
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/owner/me'),
  new NetworkFirst({
    cacheName: 'owner-me',
    networkTimeoutSeconds: 3,
    plugins: [new ExpirationPlugin({ maxAgeSeconds: 60 * 5 })],
  }),
);

// Dashboard: NetworkFirst, фолбэк на кеш для оффлайн-просмотра KPI.
registerRoute(
  ({ url }) => url.pathname.startsWith('/api/owner/dashboard'),
  new NetworkFirst({
    cacheName: 'owner-dashboard',
    networkTimeoutSeconds: 5,
    plugins: [new ExpirationPlugin({ maxAgeSeconds: 60 * 10 })],
  }),
);

type PushPayload = {
  title: string;
  body: string;
  url: string;
  tag: string;
  kind: string;
};

const ICON = '/owner/icons/icon-192.png';
const BADGE = '/owner/icons/icon-192.png';
const SCOPE_PATH = '/owner/';

const parsePayload = (event: PushEvent): PushPayload | null => {
  if (event.data === null) return null;
  try {
    const raw = event.data.json() as Partial<PushPayload>;
    if (typeof raw.title !== 'string' || typeof raw.body !== 'string') return null;
    return {
      title: raw.title,
      body: raw.body,
      url: typeof raw.url === 'string' ? raw.url : SCOPE_PATH,
      tag: typeof raw.tag === 'string' ? raw.tag : 'guard-reviews',
      kind: typeof raw.kind === 'string' ? raw.kind : 'unknown',
    };
  } catch {
    return null;
  }
};

self.addEventListener('push', (event: PushEvent) => {
  const payload = parsePayload(event);
  if (payload === null) return;

  // `vibrate` поддерживается браузерами, но отсутствует в lib.dom.d.ts.
  const options: NotificationOptions & { vibrate?: number[] } = {
    body: payload.body,
    icon: ICON,
    badge: BADGE,
    tag: payload.tag,
    data: { url: payload.url, kind: payload.kind },
    requireInteraction: true,
    vibrate: [200, 100, 200],
  };
  event.waitUntil(self.registration.showNotification(payload.title, options));
});

self.addEventListener('notificationclick', (event: NotificationEvent) => {
  event.notification.close();

  const data = (event.notification.data ?? {}) as { url?: string };
  const targetUrl = typeof data.url === 'string' && data.url !== '' ? data.url : SCOPE_PATH;

  event.waitUntil(
    self.clients
      .matchAll({ type: 'window', includeUncontrolled: true })
      .then((clients) => {
        for (const client of clients) {
          const url = new URL(client.url);
          if (url.pathname.startsWith(SCOPE_PATH)) {
            client.focus();
            client.postMessage({ type: 'navigate', url: targetUrl });
            return;
          }
        }
        return self.clients.openWindow(targetUrl);
      }),
  );
});
