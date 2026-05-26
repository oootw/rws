import { Construction } from 'lucide-react';

/**
 * Заглушка для разделов, которые подключим в Фазах 2–6.
 */
export function PlaceholderPage({ title }: { title: string }) {
  return (
    <div className="space-y-6">
      <header className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{title}</h1>
        <p className="text-sm text-ink-500">Раздел появится в следующих фазах.</p>
      </header>

      <div className="card-padded flex items-start gap-3">
        <Construction className="mt-0.5 h-5 w-5 text-warning" />
        <div className="text-sm text-ink-700">
          Каркас Owner-панели готов. Содержимое подключим, когда появятся API
          (см. <code className="rounded bg-ink-100 px-1 text-xs">backend/docs/owner-panel-plan.md</code>).
        </div>
      </div>
    </div>
  );
}
