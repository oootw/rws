import { useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';

import { Button, Field, Input, Spinner } from '@/shared/ui';

import {
  isProfileValidationError,
  useUpdateProfileMutation,
} from '../api/useUpdateProfileMutation';
import type { ProfileFieldErrors, UpdateProfileInput } from '../api/useUpdateProfileMutation';

type ProfileFormProps = {
  initial: UpdateProfileInput;
};

export function ProfileForm({ initial }: ProfileFormProps) {
  const [values, setValues] = useState<UpdateProfileInput>(initial);
  const [errors, setErrors] = useState<ProfileFieldErrors>({});
  const mutation = useUpdateProfileMutation();

  const subdomainChanged = values.subdomain !== initial.subdomain;

  const onChange =
    (field: keyof UpdateProfileInput) =>
    (event: React.ChangeEvent<HTMLInputElement>) => {
      setValues((prev) => ({ ...prev, [field]: event.target.value }));
      setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

  const onSubmit = (event: FormEvent<HTMLFormElement>): void => {
    event.preventDefault();
    setErrors({});
    mutation.mutate(values, {
      onSuccess: () => {
        toast.success(
          subdomainChanged
            ? `Адрес изменён. Войдите заново на ${values.subdomain}.otziv.space.`
            : 'Профиль обновлён.',
        );
      },
      onError: (error) => {
        if (isProfileValidationError(error)) {
          setErrors(error.fieldErrors);
        } else {
          toast.error('Не удалось сохранить профиль.');
        }
      },
    });
  };

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Профиль">
      <Field label="Имя" htmlFor="profile-name" error={errors.name}>
        <Input id="profile-name" value={values.name} onChange={onChange('name')} required />
      </Field>

      <Field label="Email" htmlFor="profile-email" error={errors.email}>
        <Input
          id="profile-email"
          type="email"
          value={values.email}
          onChange={onChange('email')}
          required
        />
      </Field>

      <Field label="Адрес (поддомен)" htmlFor="profile-subdomain" error={errors.subdomain}>
        <Input
          id="profile-subdomain"
          value={values.subdomain}
          onChange={onChange('subdomain')}
          required
        />
        {subdomainChanged && (
          <p className="text-xs text-warning">
            После смены адреса вам придётся перелогиниться на новом поддомене.
          </p>
        )}
      </Field>

      <Button type="submit" variant="primary" disabled={mutation.isPending}>
        {mutation.isPending && <Spinner size="sm" />}
        {mutation.isPending ? 'Сохраняем…' : 'Сохранить'}
      </Button>
    </form>
  );
}
