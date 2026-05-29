import { useState } from 'react';
import { toast } from 'sonner';
import { Trash2 } from 'lucide-react';

import { Button, ConfirmDialog } from '@/shared/ui';

import { useUnlinkMutation } from '../api/useUnlinkMutation';

type UnlinkChatButtonProps = {
  chatRowId: string;
  chatTitle: string;
};

export function UnlinkChatButton({ chatRowId, chatTitle }: UnlinkChatButtonProps) {
  const [isOpen, setIsOpen] = useState(false);
  const mutation = useUnlinkMutation();

  const onConfirm = (): void => {
    mutation.mutate(chatRowId, {
      onSuccess: () => {
        setIsOpen(false);
        toast.success('Чат отвязан.');
      },
      onError: () => {
        toast.error('Не удалось отвязать чат.');
      },
    });
  };

  return (
    <>
      <Button
        type="button"
        variant="ghost"
        className="text-danger"
        onClick={() => setIsOpen(true)}
        disabled={mutation.isPending}
      >
        <Trash2 className="h-4 w-4" aria-hidden="true" />
        Отвязать
      </Button>
      <ConfirmDialog
        open={isOpen}
        title="Отвязать чат?"
        description={
          <>
            «{chatTitle}» перестанет получать уведомления о негативных отзывах.
            Привязку можно будет восстановить через новую ссылку.
          </>
        }
        confirmLabel="Отвязать"
        isPending={mutation.isPending}
        onCancel={() => (mutation.isPending ? null : setIsOpen(false))}
        onConfirm={onConfirm}
      />
    </>
  );
}
