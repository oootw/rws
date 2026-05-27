import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import MockAdapter from 'axios-mock-adapter';

import { httpClient } from './httpClient';

describe('httpClient 402 interceptor', () => {
  let mock: MockAdapter;
  let originalLocation: Location;
  let assignSpy: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    mock = new MockAdapter(httpClient);
    originalLocation = window.location;
    assignSpy = vi.fn();
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { ...originalLocation, pathname: '/owner/places', assign: assignSpy },
    });
  });

  afterEach(() => {
    mock.restore();
    Object.defineProperty(window, 'location', { configurable: true, value: originalLocation });
  });

  it('редиректит на /owner/subscription при 402 если пользователь не там', async () => {
    mock.onPost('/places').reply(402, { code: 'subscription_expired', message: 'expired' });

    await expect(httpClient.post('/places', {})).rejects.toMatchObject({
      response: { status: 402 },
    });
    expect(assignSpy).toHaveBeenCalledWith('/owner/subscription');
  });

  it('не редиректит если пользователь уже на /owner/subscription', async () => {
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { ...originalLocation, pathname: '/owner/subscription', assign: assignSpy },
    });
    mock.onPost('/subscription/init-payment').reply(402, { code: 'subscription_expired' });

    await expect(httpClient.post('/subscription/init-payment', {})).rejects.toMatchObject({
      response: { status: 402 },
    });
    expect(assignSpy).not.toHaveBeenCalled();
  });

  it('не трогает другие статусы', async () => {
    mock.onPost('/places').reply(422, { message: 'invalid' });

    await expect(httpClient.post('/places', {})).rejects.toMatchObject({
      response: { status: 422 },
    });
    expect(assignSpy).not.toHaveBeenCalled();
  });
});
