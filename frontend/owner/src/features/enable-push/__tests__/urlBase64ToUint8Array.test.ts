import { describe, expect, it } from 'vitest';

import { urlBase64ToUint8Array } from '../lib/urlBase64ToUint8Array';

describe('urlBase64ToUint8Array', () => {
  it('декодирует base64url (replaces - and _)', () => {
    // "Hello" → SGVsbG8 в base64url
    const result = urlBase64ToUint8Array('SGVsbG8');
    expect(Array.from(result)).toEqual([72, 101, 108, 108, 111]);
  });

  it('добавляет padding если длина не кратна 4', () => {
    // "M" → TQ (base64 без padding)
    const result = urlBase64ToUint8Array('TQ');
    expect(Array.from(result)).toEqual([77]);
  });
});
