import { useNavigate, useSearchParams } from 'react-router-dom';

import { Card } from '@/shared/ui';
import { CodeForm, TelegramLoginCta } from '@/features/auth-by-telegram';

const BOT_USERNAME = (import.meta.env.VITE_TELEGRAM_BOT_USERNAME as string | undefined) ?? null;

export function LoginPage() {
  const [params] = useSearchParams();
  const navigate = useNavigate();

  return (
    <Card className="w-full max-w-md space-y-6">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold tracking-tight">Вход в кабинет</h1>
        <p className="text-sm text-ink-500">
          Получите одноразовый код в Telegram-боте — команда /login. Затем вернитесь
          сюда и введите код или откройте кнопку из бота.
        </p>
      </header>

      <TelegramLoginCta botUsername={BOT_USERNAME} />

      <CodeForm
        initialCode={params.get('code') ?? ''}
        onSuccess={() => navigate('/', { replace: true })}
      />
    </Card>
  );
}
