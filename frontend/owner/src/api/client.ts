import axios from 'axios';

/**
 * Базовый axios-клиент для Owner-панели.
 *
 * Sanctum SPA-сессии работают через cookies — обязательно withCredentials.
 * Перед мутациями вызвать GET /sanctum/csrf-cookie (см. ensureCsrf()).
 */
export const api = axios.create({
  baseURL: '/api/owner',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

let csrfFetched = false;

export async function ensureCsrf(): Promise<void> {
  if (csrfFetched) {
    return;
  }

  await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
  csrfFetched = true;
}
