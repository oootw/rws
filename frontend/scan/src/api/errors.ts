export type ApiError = {
  readonly tag: 'ApiError';
  readonly message: string;
  readonly status: number;
  readonly code?: string;
  readonly validationErrors?: Record<string, string[]>;
};

export function createApiError(
  message: string,
  status: number,
  code?: string,
  validationErrors?: Record<string, string[]>,
): ApiError {
  return {
    tag: 'ApiError',
    message,
    status,
    code,
    validationErrors,
  };
}

export function isApiError(value: unknown): value is ApiError {
  return (
    typeof value === 'object'
    && value !== null
    && (value as ApiError).tag === 'ApiError'
  );
}

export function getFirstValidationError(error: ApiError): string | undefined {
  if (!error.validationErrors) {
    return undefined;
  }

  const [firstFieldErrors] = Object.values(error.validationErrors);

  return firstFieldErrors?.[0];
}
