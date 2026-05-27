import { useState } from 'react';

import { Button, Card, Spinner } from '@/shared/ui';

import { useIssueTelegramCodeMutation } from '../api/useIssueTelegramCodeMutation';
import type { IssuedTelegramCode } from '../api/useIssueTelegramCodeMutation';

type TelegramCodeCardProps = {
  isConnected: boolean;
};

const formatExpiry = (iso: string): string => {
  const date = new Date(iso);
  return new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' }).format(date);
};

export function TelegramCodeCard({ isConnected }: TelegramCodeCardProps) {
  const [issued, setIssued] = useState<IssuedTelegramCode | null>(null);
  const mutation = useIssueTelegramCodeMutation();

  if (!isConnected) {
    return (
      <Card as="section" className="space-y-2 text-sm text-ink-500">
        <h2 className="text-base font-semibold text-ink-900">Telegram</h2>
        <p>Аккаунт не привязан. Откройте бота и используйте команду /start, чтобы привязать Telegram.</p>
      </Card>
    );
  }

  return (
    <Card as="section" className="space-y-3">
      <header className="space-y-1">
        <h2 className="text-base font-semibold text-ink-900">Telegram</h2>
        <p className="text-xs text-ink-500">Привязан. Можно выпустить fresh-код для входа.</p>
      </header>

      <Button
        variant="ghost"
        onClick={() =>
          mutation.mutate(undefined, {
            onSuccess: setIssued,
          })
        }
        disabled={mutation.isPending}
      >
        {mutation.isPending && <Spinner size="sm" />}
        {mutation.isPending ? 'Запрашиваем…' : 'Получить код'}
      </Button>

      {issued !== null && (
        <div className="rounded-xl bg-ink-100 px-3 py-2 text-sm">
          <p className="font-mono text-lg tracking-widest">{issued.code}</p>
          <p className="text-xs text-ink-500">Действует до {formatExpiry(issued.expires_at)}.</p>
        </div>
      )}

      {mutation.isError && (
        <p className="text-xs text-danger" role="alert">
          Не удалось получить код.
        </p>
      )}
    </Card>
  );
}
