import { useDashboardQuery } from '@/entities/analytics';
import { useSessionQuery } from '@/entities/session';
import { KpiCards } from '@/widgets/kpi-cards';

export function DashboardPage() {
  const session = useSessionQuery();
  const dashboard = useDashboardQuery();

  return (
    <div className="space-y-6 sm:space-y-8">
      <header className="space-y-1">
        <p className="text-sm text-ink-500">
          {session.data ? `Здравствуйте, ${session.data.name}` : 'Добро пожаловать'}
        </p>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Кабинет владельца</h1>
      </header>

      <KpiCards data={dashboard.data} isLoading={dashboard.isPending} />
    </div>
  );
}
