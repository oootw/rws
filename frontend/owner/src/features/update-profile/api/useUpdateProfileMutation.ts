import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationResult } from '@tanstack/react-query';

import { ensureCsrf, httpClient, isAxiosError } from '@/shared/api';
import { sessionQueryKeys } from '@/entities/session';
import type { OwnerMe } from '@/entities/session';

export type UpdateProfileInput = {
  name: string;
  email: string;
  subdomain: string;
};

export type ProfileFieldErrors = Partial<Record<keyof UpdateProfileInput, string>>;

export type ProfileValidationError = Error & {
  name: 'ProfileValidationError';
  fieldErrors: ProfileFieldErrors;
};

const PROFILE_VALIDATION_ERROR = 'ProfileValidationError' as const;

const makeProfileValidationError = (
  fieldErrors: ProfileFieldErrors,
  message: string,
): ProfileValidationError => {
  const error = new Error(message) as ProfileValidationError;
  error.name = PROFILE_VALIDATION_ERROR;
  error.fieldErrors = fieldErrors;
  return error;
};

export const isProfileValidationError = (
  error: unknown,
): error is ProfileValidationError =>
  error instanceof Error && error.name === PROFILE_VALIDATION_ERROR;

type Envelope = { data: OwnerMe };

const firstError = (errors: Record<string, string[] | undefined>): ProfileFieldErrors => {
  const fields: (keyof UpdateProfileInput)[] = ['name', 'email', 'subdomain'];
  const out: ProfileFieldErrors = {};
  for (const field of fields) {
    const messages = errors[field];
    if (messages !== undefined && messages.length > 0) out[field] = messages[0];
  }
  return out;
};

const updateProfile = async (input: UpdateProfileInput): Promise<OwnerMe> => {
  await ensureCsrf();
  try {
    const response = await httpClient.patch<Envelope>('/profile', input);
    return response.data.data;
  } catch (error) {
    if (isAxiosError(error) && error.response?.status === 422) {
      const data = error.response.data as { message?: string; errors?: Record<string, string[]> };
      throw makeProfileValidationError(
        firstError(data.errors ?? {}),
        data.message ?? 'Проверьте поля.',
      );
    }
    throw error;
  }
};

export const useUpdateProfileMutation = (): UseMutationResult<OwnerMe, Error, UpdateProfileInput> => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: updateProfile,
    onSuccess: (owner) => {
      queryClient.setQueryData<OwnerMe>(sessionQueryKeys.me(), owner);
    },
  });
};
