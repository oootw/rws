import axios from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { httpClient } from '@/shared/api';

import { isPushPermissionDenied, registerPush } from '../lib/registerPush';

type Fakes = {
  permission: NotificationPermission;
  subscribeResult?: {
    endpoint: string;
    keys: { p256dh: string; auth: string };
  };
  subscribeRejects?: Error;
};

const fakeBrowser = (f: Fakes): { subscribeMock: ReturnType<typeof vi.fn> } => {
  Object.defineProperty(global, 'Notification', {
    value: { requestPermission: vi.fn().mockResolvedValue(f.permission) },
    configurable: true,
  });

  const subscribeMock = vi.fn();
  if (f.subscribeRejects !== undefined) {
    subscribeMock.mockRejectedValue(f.subscribeRejects);
  } else {
    const json = f.subscribeResult ?? {
      endpoint: 'https://fcm.googleapis.com/x/abc',
      keys: { p256dh: 'p', auth: 'a' },
    };
    subscribeMock.mockResolvedValue({ toJSON: () => json });
  }

  Object.defineProperty(navigator, 'serviceWorker', {
    value: {
      ready: Promise.resolve({
        pushManager: { subscribe: subscribeMock },
      }),
    },
    configurable: true,
  });

  return { subscribeMock };
};

describe('registerPush', () => {
  beforeEach(() => {
    vi.spyOn(httpClient, 'post').mockResolvedValue({ data: {} });
    // ensureCsrf зовёт axios.get('/sanctum/csrf-cookie') — мокаем сетевой
    // вызов. once() кэширует результат, так что повторные вызовы no-op.
    vi.spyOn(axios, 'get').mockResolvedValue({ data: {} });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('подписывается и POSTит endpoint+keys на бэк', async () => {
    fakeBrowser({ permission: 'granted' });

    await registerPush({ vapidPublicKey: 'SGVsbG8', userAgent: 'Chrome/120' });

    expect(httpClient.post).toHaveBeenCalledWith('/push/subscribe', {
      endpoint: 'https://fcm.googleapis.com/x/abc',
      keys: { p256dh: 'p', auth: 'a' },
      user_agent: 'Chrome/120',
    });
  });

  it('бросает PushPermissionDenied, если пользователь отказал', async () => {
    fakeBrowser({ permission: 'denied' });

    try {
      await registerPush({ vapidPublicKey: 'SGVsbG8', userAgent: null });
      expect.fail('Should have thrown');
    } catch (error) {
      expect(isPushPermissionDenied(error)).toBe(true);
      expect(httpClient.post).not.toHaveBeenCalled();
    }
  });

  it('передаёт applicationServerKey в виде Uint8Array', async () => {
    const { subscribeMock } = fakeBrowser({ permission: 'granted' });

    await registerPush({ vapidPublicKey: 'SGVsbG8', userAgent: null });

    const args = subscribeMock.mock.calls[0][0];
    expect(args.userVisibleOnly).toBe(true);
    expect(args.applicationServerKey).toBeInstanceOf(Uint8Array);
  });
});
