import { useState } from 'react';
import { toast } from 'sonner';

import {
  usePushConfigQuery,
  useMyPushSubscriptionsQuery,
} from '@/entities/push-subscription';
import type { PushSubscriptionDevice } from '@/entities/push-subscription';
import {
  EnablePushButton,
  IosAddToHomeHint,
  PushPermissionDeniedHint,
  detectPushSupport,
  useDisablePushMutation,
} from '@/features/enable-push';
import { Button, Card, Skeleton } from '@/shared/ui';

const formatDate = (iso: string | null): string => {
  if (iso === null) return '—';
  return new Date(iso).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const deviceLabel = (device: PushSubscriptionDevice): string =>
  device.user_agent !== null && device.user_agent !== ''
    ? device.user_agent
    : 'Неизвестное устройство';

function DeviceRow({ device }: { device: PushSubscriptionDevice }) {
  const disable = useDisablePushMutation();

  const onRevoke = (): void => {
    disable.mutate(
      { endpoint: device.endpoint },
      {
        onSuccess: () => toast.success('Подписка отозвана.'),
        onError: () => toast.error('Не удалось отозвать подписку.'),
      },
    );
  };

  return (
    <li className="flex items-start justify-between gap-3 rounded-2xl border border-ink-100 p-3">
      <div className="min-w-0 flex-1">
        <p className="truncate text-sm font-medium text-ink-900">{deviceLabel(device)}</p>
        <p className="text-xs text-ink-500">
          Зарегистрировано {formatDate(device.created_at)}
          {device.last_seen_at !== null && ` · активно ${formatDate(device.last_seen_at)}`}
        </p>
      </div>
      <Button type="button" variant="ghost" onClick={onRevoke} disabled={disable.isPending}>
        Отозвать
      </Button>
    </li>
  );
}

export function PushSettingsCard() {
  const config = usePushConfigQuery();
  const devices = useMyPushSubscriptionsQuery();
  const [permissionDenied, setPermissionDenied] = useState(false);
  const support = detectPushSupport();

  if (config.isPending || devices.isPending) {
    return (
      <Card as="section" className="space-y-3" aria-busy="true">
        <Skeleton className="h-5 w-1/3" />
        <Skeleton className="h-10 w-full rounded-xl" />
      </Card>
    );
  }

  if (config.data?.enabled !== true) {
    return null;
  }

  return (
    <Card as="section" className="space-y-4">
      <header className="space-y-1">
        <h2 className="text-base font-semibold text-ink-900">Push-уведомления</h2>
        <p className="text-sm text-ink-500">
          Мгновенные пуши о новых негативных отзывах. Работают параллельно
          с Telegram.
        </p>
      </header>

      {support.requiresIosInstall && <IosAddToHomeHint />}
      {permissionDenied && <PushPermissionDeniedHint />}

      {support.supported && (
        <EnablePushButton
          vapidPublicKey={config.data.vapid_public_key}
          onPermissionDenied={() => setPermissionDenied(true)}
        />
      )}

      {devices.data !== undefined && devices.data.length > 0 && (
        <ul className="space-y-2">
          {devices.data.map((device) => (
            <DeviceRow key={device.id} device={device} />
          ))}
        </ul>
      )}
    </Card>
  );
}
