import { Link } from 'react-router-dom';

import { Card } from '@/shared/ui';

const primaryLinkClass =
  'mt-6 inline-flex items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-medium text-accent-fg transition hover:brightness-105 active:brightness-95';

export function NotFoundPage() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-canvas p-6">
      <Card className="max-w-md text-center">
        <h1 className="text-2xl font-semibold tracking-tight">Страница не найдена</h1>
        <p className="mt-2 text-sm text-ink-500">
          Возможно, вы перешли по устаревшей ссылке.
        </p>
        <Link to="/" className={primaryLinkClass}>
          На главную
        </Link>
      </Card>
    </div>
  );
}
