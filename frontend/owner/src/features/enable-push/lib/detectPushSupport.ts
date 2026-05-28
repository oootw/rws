/**
 * Что Web Push доступен прямо сейчас + какой UX-hint показать.
 *
 *  - supported: браузер поддерживает Service Worker + PushManager + Notification.
 *  - requiresIosInstall: Safari iOS требует A2HS (Add to Home Screen) для пушей.
 *    В standalone-режиме (запуск с домашнего экрана) — пуши работают,
 *    в обычной Safari-вкладке — нет, нужен hint.
 */
export type PushSupport = {
  supported: boolean;
  requiresIosInstall: boolean;
};

const isIos = (ua: string): boolean =>
  /iPad|iPhone|iPod/.test(ua) ||
  // iPadOS 13+ маскируется под Mac, но имеет touchPoints.
  (ua.includes('Mac') && typeof navigator !== 'undefined' && navigator.maxTouchPoints > 1);

const isStandalone = (): boolean => {
  if (typeof window === 'undefined') return false;
  if (window.matchMedia('(display-mode: standalone)').matches) return true;
  // Safari legacy.
  return Boolean((window.navigator as { standalone?: boolean }).standalone);
};

export const detectPushSupport = (): PushSupport => {
  if (typeof window === 'undefined' || typeof navigator === 'undefined') {
    return { supported: false, requiresIosInstall: false };
  }

  const hasApi =
    'serviceWorker' in navigator &&
    'PushManager' in window &&
    'Notification' in window;

  if (!hasApi) {
    return { supported: false, requiresIosInstall: false };
  }

  if (isIos(navigator.userAgent) && !isStandalone()) {
    return { supported: false, requiresIosInstall: true };
  }

  return { supported: true, requiresIosInstall: false };
};
