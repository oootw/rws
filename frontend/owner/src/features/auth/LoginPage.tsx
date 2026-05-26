import { Send } from 'lucide-react';

/**
 * Login-каркас Фазы 0. В Фазе 1 здесь будет:
 *  - кнопка-deep-link в Telegram-бот для magic-code;
 *  - инпут одноразового кода и обмен через POST /api/owner/auth/exchange.
 */
export function LoginPage() {
  return (
    <div className="w-full max-w-md card-padded space-y-6">
      <header className="space-y-2">
        <h1 className="text-2xl font-semibold tracking-tight">Вход в кабинет</h1>
        <p className="text-sm text-ink-500">
          Войдите через Telegram-бот — он отправит одноразовый код и кнопку для
          быстрого входа.
        </p>
      </header>

      <button type="button" className="btn-primary w-full" disabled>
        <Send className="h-4 w-4" />
        Открыть бот (скоро)
      </button>

      <div className="space-y-3">
        <label htmlFor="login-code" className="text-sm font-medium text-ink-700">
          Одноразовый код
        </label>
        <input
          id="login-code"
          className="input"
          placeholder="123456"
          inputMode="numeric"
          autoComplete="one-time-code"
          maxLength={6}
          disabled
        />
        <p className="text-xs text-ink-400">
          Поле станет активным после подключения Telegram-логина в Фазе 1.
        </p>
      </div>
    </div>
  );
}
