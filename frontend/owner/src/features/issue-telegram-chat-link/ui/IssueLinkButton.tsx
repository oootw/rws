import { useState } from 'react';
import { toast } from 'sonner';
import { Link as LinkIcon } from 'lucide-react';

import { Button } from '@/shared/ui';
import type { IssuedChatLink } from '@/entities/telegram-chat';

import { useIssueLinkMutation } from '../api/useIssueLinkMutation';

type IssueLinkButtonProps = {
  /**
   * Передаёт владельцу выданный deep-link — карточка показывает его и
   * подсказку «откройте в Telegram», чтобы привязка отработала из бот-чата.
   */
  onIssued?: (link: IssuedChatLink) => void;
};

export function IssueLinkButton({ onIssued }: IssueLinkButtonProps) {
  const [busy, setBusy] = useState(false);
  const mutation = useIssueLinkMutation();

  const onClick = (): void => {
    setBusy(true);
    mutation.mutate(undefined, {
      onSuccess: (link) => {
        onIssued?.(link);
        // Открываем в новой вкладке — мобильный Telegram перехватит ссылку
        // через universal-link и предложит выбрать чат.
        window.open(link.deep_link, '_blank', 'noopener');
      },
      onError: () => {
        toast.error('Не удалось получить ссылку. Попробуйте ещё раз.');
      },
      onSettled: () => setBusy(false),
    });
  };

  return (
    <Button type="button" onClick={onClick} disabled={busy}>
      <LinkIcon className="h-4 w-4" aria-hidden="true" />
      {busy ? 'Готовим ссылку…' : 'Привязать Telegram-чат'}
    </Button>
  );
}
