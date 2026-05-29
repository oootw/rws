import type { ReactNode } from 'react';

import { useSessionQuery } from '@/entities/session';
import { FeatureGate, UpsellCard } from '@/entities/features';
import { ProfileForm } from '@/features/update-profile';
import { TelegramCodeCard } from '@/features/issue-telegram-code';
import { PushSettingsCard } from '@/widgets/push-settings-card';
import { TelegramChatsCard } from '@/widgets/telegram-chats-card';
import { Card, Skeleton } from '@/shared/ui';

export function ProfilePage() {
  const session = useSessionQuery();
  const owner = session.data;

  const renderContent = (): ReactNode => {
    if (owner !== null && owner !== undefined) {
      return (
        <>
          <Card as="section" className="space-y-4">
            <h2 className="text-base font-semibold text-ink-900">Основные данные</h2>
            <ProfileForm
              initial={{ name: owner.name, email: owner.email, subdomain: owner.subdomain }}
            />
          </Card>
          <TelegramCodeCard isConnected={owner.telegram_connected} />
          <PushSettingsCard />
          <FeatureGate
            feature="shared_telegram_chat"
            fallback={
              <UpsellCard
                title="Общий Telegram-чат на команду"
                description="Привязывайте групповой чат, чтобы вся команда видела уведомления о негативных отзывах одновременно."
              />
            }
          >
            <TelegramChatsCard />
          </FeatureGate>
        </>
      );
    }
    if (session.isPending) {
      return (
        <Card as="section" className="space-y-4" aria-busy="true" aria-live="polite">
          <Skeleton className="h-5 w-1/3" />
          <Skeleton className="h-10 w-full rounded-xl" />
          <Skeleton className="h-10 w-full rounded-xl" />
          <Skeleton className="h-10 w-full rounded-xl" />
          <Skeleton className="h-10 w-32 rounded-xl" />
        </Card>
      );
    }
    return <Card className="text-sm text-ink-500">Сессия не найдена.</Card>;
  };

  return (
    <div className="space-y-6 sm:space-y-8">
      <header className="space-y-1">
        <p className="text-sm text-ink-500">Настройки аккаунта</p>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Профиль</h1>
      </header>

      {renderContent()}
    </div>
  );
}
