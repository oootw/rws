import axios, { isAxiosError } from 'axios';

/**
 * Базовый axios-клиент для Owner-панели.
 *
 * Sanctum SPA-сессии работают через cookies — обязательно withCredentials.
 * Перед мутациями вызвать ensureCsrf().
 */
export const httpClient = axios.create({
  baseURL: '/api/owner',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

const once = <T>(fn: () => Promise<T>): (() => Promise<T>) => {
  let cached: Promise<T> | null = null;
  return () => {
    if (cached === null) {
      cached = fn().catch((error) => {
        cached = null;
        throw error;
      });
    }
    return cached;
  };
};

export const ensureCsrf = once(async () => {
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
});

/**
 * Re-export: чтобы слайсы могли проверять http-ошибки без прямого импорта axios.
 */
export { isAxiosError };
