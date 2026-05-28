import { useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/shared/ui';

import { useEnablePushMutation } from '../api/useEnablePushMutation';
import { isPushPermissionDenied } from '../lib/registerPush';

type EnablePushButtonProps = {
  vapidPublicKey: string;
  onPermissionDenied: () => void;
};

export function EnablePushButton({ vapidPublicKey, onPermissionDenied }: EnablePushButtonProps) {
  const [busy, setBusy] = useState(false);
  const mutation = useEnablePushMutation();

  const onClick = (): void => {
    setBusy(true);
    mutation.mutate(
      { vapidPublicKey, userAgent: navigator.userAgent },
      {
        onSuccess: () => {
          toast.success('Пуши включены на этом устройстве.');
        },
        onError: (error) => {
          if (isPushPermissionDenied(error)) {
            onPermissionDenied();
            return;
          }
          toast.error('Не удалось включить уведомления.');
        },
        onSettled: () => setBusy(false),
      },
    );
  };

  return (
    <Button type="button" onClick={onClick} disabled={busy}>
      {busy ? 'Включаем…' : 'Включить push-уведомления'}
    </Button>
  );
}
