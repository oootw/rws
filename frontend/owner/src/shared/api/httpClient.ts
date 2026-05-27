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

const SUBSCRIPTION_PATH = '/owner/subscription';

/**
 * Подписочный гард: бекенд возвращает 402 на платные мутации, если подписка
 * истекла. Перенаправляем пользователя на страницу подписки (если он ещё не
 * там) и пробрасываем ошибку дальше — React Query увидит её в `onError`.
 *
 * Внешний редирект (`window.location.assign`) намеренный: он сбрасывает
 * React-state и форсит свежую загрузку, что естественно после блокировки.
 */
httpClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (
      isAxiosError(error) &&
      error.response?.status === 402 &&
      typeof window !== 'undefined' &&
      !window.location.pathname.startsWith(SUBSCRIPTION_PATH)
    ) {
      window.location.assign(SUBSCRIPTION_PATH);
    }
    return Promise.reject(error);
  },
);

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
