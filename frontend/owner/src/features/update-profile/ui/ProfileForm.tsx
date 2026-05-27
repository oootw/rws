import { useState } from 'react';
import type { FormEvent } from 'react';
import { toast } from 'sonner';

import { Button, Input } from '@/shared/ui';

import {
  isProfileValidationError,
  useUpdateProfileMutation,
} from '../api/useUpdateProfileMutation';
import type { ProfileFieldErrors, UpdateProfileInput } from '../api/useUpdateProfileMutation';

type ProfileFormProps = {
  initial: UpdateProfileInput;
};

const labelClass = 'block text-xs font-medium text-ink-500';
const fieldClass = 'space-y-1.5';
const errorClass = 'text-xs text-danger';

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
      <div className={fieldClass}>
        <label htmlFor="profile-name" className={labelClass}>Имя</label>
        <Input id="profile-name" value={values.name} onChange={onChange('name')} required />
        {errors.name !== undefined && <p className={errorClass}>{errors.name}</p>}
      </div>

      <div className={fieldClass}>
        <label htmlFor="profile-email" className={labelClass}>Email</label>
        <Input
          id="profile-email"
          type="email"
          value={values.email}
          onChange={onChange('email')}
          required
        />
        {errors.email !== undefined && <p className={errorClass}>{errors.email}</p>}
      </div>

      <div className={fieldClass}>
        <label htmlFor="profile-subdomain" className={labelClass}>Адрес (поддомен)</label>
        <Input
          id="profile-subdomain"
          value={values.subdomain}
          onChange={onChange('subdomain')}
          required
        />
        {errors.subdomain !== undefined && <p className={errorClass}>{errors.subdomain}</p>}
        {subdomainChanged && (
          <p className="text-xs text-warning">
            После смены адреса вам придётся перелогиниться на новом поддомене.
          </p>
        )}
      </div>

      <Button type="submit" variant="primary" disabled={mutation.isPending}>
        {mutation.isPending ? 'Сохраняем…' : 'Сохранить'}
      </Button>
    </form>
  );
}
