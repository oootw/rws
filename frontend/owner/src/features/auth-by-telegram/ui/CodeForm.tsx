import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';

import { Button, Field, Input, Spinner } from '@/shared/ui';
import { isAxiosError } from '@/shared/api';

import { useExchangeCodeMutation } from '../api/useExchangeCodeMutation';

type CodeFormProps = {
  initialCode?: string;
  onSuccess?: () => void;
};

const CODE_PATTERN = /^\d{6}$/;

const errorMessageFor = (error: unknown): string => {
  if (isAxiosError(error)) {
    const message = error.response?.data?.message;
    if (typeof message === 'string' && message.length > 0) {
      return message;
    }
  }
  return 'Не удалось проверить код. Попробуйте позже.';
};

export function CodeForm({ initialCode = '', onSuccess }: CodeFormProps) {
  const [code, setCode] = useState(initialCode);
  const exchange = useExchangeCodeMutation();

  useEffect(() => {
    if (CODE_PATTERN.test(initialCode)) {
      exchange.mutate(initialCode, {
        onSuccess: () => onSuccess?.(),
      });
    }
    // initialCode проверяем один раз при монтировании; повторно — через явный submit.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
    event.preventDefault();
    if (!CODE_PATTERN.test(code)) {
      return;
    }
    exchange.mutate(code, {
      onSuccess: () => onSuccess?.(),
    });
  };

  const isSubmittable = CODE_PATTERN.test(code) && !exchange.isPending;

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      <Field
        label="Одноразовый код"
        htmlFor="login-code"
        error={exchange.isError ? errorMessageFor(exchange.error) : undefined}
        hint={exchange.isError ? undefined : 'Получите код по команде /login в Telegram-боте.'}
      >
        <Input
          id="login-code"
          placeholder="123456"
          inputMode="numeric"
          autoComplete="one-time-code"
          maxLength={6}
          value={code}
          onChange={(event) => setCode(event.target.value.replace(/\D+/g, ''))}
          disabled={exchange.isPending}
        />
      </Field>
      <Button variant="primary" className="w-full" type="submit" disabled={!isSubmittable}>
        {exchange.isPending && <Spinner size="sm" />}
        {exchange.isPending ? 'Проверяем…' : 'Войти'}
      </Button>
    </form>
  );
}
