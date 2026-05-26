import { Send } from 'lucide-react';

import { buildTelegramDeepLink } from '../lib/buildTelegramDeepLink';

type TelegramLoginCtaProps = {
  botUsername: string | null;
};

const baseClass =
  'inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-medium text-accent-fg transition hover:brightness-105 active:brightness-95 disabled:cursor-not-allowed disabled:opacity-50';

/**
 * CTA-кнопка «открыть бот». Если username бота не сконфигурирован — disabled.
 */
export function TelegramLoginCta({ botUsername }: TelegramLoginCtaProps) {
  if (botUsername === null) {
    return (
      <button type="button" className={baseClass} disabled>
        <Send className="h-4 w-4" />
        Откройте бот в Telegram
      </button>
    );
  }

  return (
    <a
      href={buildTelegramDeepLink({ botUsername })}
      target="_blank"
      rel="noopener noreferrer"
      className={baseClass}
    >
      <Send className="h-4 w-4" />
      Откройте бот в Telegram
    </a>
  );
}
