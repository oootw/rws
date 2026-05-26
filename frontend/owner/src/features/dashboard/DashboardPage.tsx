import { ArrowRight, MapPin, MessageSquare, Star, TrendingUp } from 'lucide-react';
import { Link } from 'react-router-dom';

const stats = [
  { label: 'Сканы за 7 дней', value: '—', icon: TrendingUp },
  { label: 'Отзывы', value: '—', icon: MessageSquare },
  { label: 'Точки', value: '—', icon: MapPin },
  { label: 'Средний рейтинг', value: '—', icon: Star },
];

/**
 * Заглушка дашборда. Реальные KPI и графики появятся в Фазе 2.
 */
export function DashboardPage() {
  return (
    <div className="space-y-6 sm:space-y-8">
      <header className="space-y-1">
        <p className="text-sm text-ink-500">Добро пожаловать</p>
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Кабинет владельца</h1>
      </header>

      <section
        aria-label="Сводка"
        className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4"
      >
        {stats.map((stat) => (
          <article key={stat.label} className="card-padded">
            <div className="flex items-center gap-2 text-ink-500">
              <stat.icon className="h-4 w-4" />
              <span className="text-xs font-medium uppercase tracking-wide">
                {stat.label}
              </span>
            </div>
            <div className="mt-3 text-3xl font-semibold tracking-tight">{stat.value}</div>
          </article>
        ))}
      </section>

      <section className="card-padded">
        <div className="flex items-start justify-between gap-4">
          <div className="space-y-1">
            <h2 className="text-base font-semibold">Кабинет в разработке</h2>
            <p className="text-sm text-ink-500">
              Фаза 0 — каркас приложения. Полные данные подключим в следующих фазах
              (вход через Telegram, точки, отзывы, подписка).
            </p>
          </div>
          <Link to="/profile" className="btn-ghost shrink-0">
            Профиль
            <ArrowRight className="h-4 w-4" />
          </Link>
        </div>
      </section>
    </div>
  );
}
