import { afterEach, describe, expect, it, vi } from 'vitest';

import { detectPushSupport } from '../lib/detectPushSupport';

const mockUserAgent = (ua: string): void => {
  Object.defineProperty(navigator, 'userAgent', { value: ua, configurable: true });
};

const mockMaxTouchPoints = (value: number): void => {
  Object.defineProperty(navigator, 'maxTouchPoints', { value, configurable: true });
};

const mockStandalone = (value: boolean): void => {
  vi.spyOn(window, 'matchMedia').mockReturnValue({
    matches: value,
    media: '',
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  } as MediaQueryList);
};

const mockWindowFlags = (overrides: { Notification?: unknown; PushManager?: unknown } = {}): void => {
  if (overrides.Notification !== undefined) {
    Object.defineProperty(window, 'Notification', { value: overrides.Notification, configurable: true });
  }
  if (overrides.PushManager !== undefined) {
    Object.defineProperty(window, 'PushManager', { value: overrides.PushManager, configurable: true });
  }
};

const ensureServiceWorker = (present: boolean): void => {
  if (present) {
    Object.defineProperty(navigator, 'serviceWorker', { value: {}, configurable: true });
  } else {
    // @ts-expect-error — намеренно удаляем
    delete navigator.serviceWorker;
  }
};

describe('detectPushSupport', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('supported=true на обычном Chrome', () => {
    mockUserAgent('Mozilla/5.0 Chrome/120 Safari/537.36');
    mockMaxTouchPoints(0);
    mockWindowFlags({ Notification: function () {}, PushManager: function () {} });
    ensureServiceWorker(true);
    mockStandalone(false);

    expect(detectPushSupport()).toEqual({ supported: true, requiresIosInstall: false });
  });

  it('supported=false без serviceWorker', () => {
    mockUserAgent('Mozilla/5.0 Chrome/120 Safari/537.36');
    mockWindowFlags({ Notification: function () {}, PushManager: function () {} });
    ensureServiceWorker(false);
    mockStandalone(false);

    expect(detectPushSupport()).toEqual({ supported: false, requiresIosInstall: false });
  });

  it('iOS Safari без standalone → requiresIosInstall', () => {
    mockUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605');
    mockMaxTouchPoints(5);
    mockWindowFlags({ Notification: function () {}, PushManager: function () {} });
    ensureServiceWorker(true);
    mockStandalone(false);

    expect(detectPushSupport()).toEqual({ supported: false, requiresIosInstall: true });
  });

  it('iOS standalone → supported=true', () => {
    mockUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605');
    mockMaxTouchPoints(5);
    mockWindowFlags({ Notification: function () {}, PushManager: function () {} });
    ensureServiceWorker(true);
    mockStandalone(true);

    expect(detectPushSupport()).toEqual({ supported: true, requiresIosInstall: false });
  });
});
