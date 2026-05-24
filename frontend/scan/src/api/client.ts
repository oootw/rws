import type {
  ApiErrorResponse,
  OkResponse,
  PlacePublicResponse,
  RedirectResponse,
  ReviewPayload,
} from '@guard-reviews/shared/types';
import { createApiError, type ApiError } from './errors';

const API_BASE = '/api/public';

const buildHeaders = (): Record<string, string> => {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (import.meta.env.DEV || import.meta.env.VITE_ALLOW_TENANT_QUERY === '1') {
    const tenant = new URLSearchParams(window.location.search).get('tenant');

    if (tenant) {
      headers['X-Tenant-Slug'] = tenant;
    }
  }

  return headers;
};

const parseError = async (response: Response): Promise<ApiError> => {
  let payload: ApiErrorResponse = { message: 'Произошла ошибка.' };

  try {
    payload = (await response.json()) as ApiErrorResponse;
  } catch {
    // Оставляем сообщение по умолчанию, если тело не JSON.
  }

  return createApiError(
    payload.message,
    response.status,
    payload.code,
    payload.errors,
  );
};

const request = async <T>(path: string, init?: RequestInit): Promise<T> => {
  const response = await fetch(`${API_BASE}${path}`, {
    ...init,
    headers: {
      ...buildHeaders(),
      ...(init?.headers as Record<string, string> | undefined),
    },
  });

  if (!response.ok) {
    throw await parseError(response);
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return response.json() as Promise<T>;
};

export function getPlaceIdFromPath(): string | null {
  const match = window.location.pathname.match(/\/s\/([0-9a-f-]{36})/i);

  return match?.[1] ?? null;
}

export const fetchPlace = (placeId: string): Promise<PlacePublicResponse> =>
  request<PlacePublicResponse>(`/places/${placeId}`);

export const logScan = (placeId: string): Promise<OkResponse> =>
  request<OkResponse>(`/places/${placeId}/scan`, { method: 'POST' });

export const redirectToPlatform = (
  placeId: string,
  platformType: string,
): Promise<RedirectResponse> =>
  request<RedirectResponse>(`/places/${placeId}/redirect`, {
    method: 'POST',
    body: JSON.stringify({ platform_type: platformType }),
  });

export const submitReview = (placeId: string, payload: ReviewPayload): Promise<OkResponse> =>
  request<OkResponse>(`/places/${placeId}/reviews`, {
    method: 'POST',
    body: JSON.stringify(payload),
  });

export const reportCriticalError = (placeId: string, context: string): Promise<OkResponse> =>
  request<OkResponse>(`/places/${placeId}/critical-error`, {
    method: 'POST',
    body: JSON.stringify({ context }),
  });

export { createApiError, isApiError, getFirstValidationError } from './errors';
export type { ApiError } from './errors';
