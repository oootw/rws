import { NavLink, Outlet } from 'react-router-dom';
import { LayoutGrid, MapPin, MessageSquare, User, Wallet } from 'lucide-react';
import type { ComponentType, SVGProps } from 'react';

type NavItem = {
  to: string;
  label: string;
  icon: ComponentType<SVGProps<SVGSVGElement>>;
  end?: boolean;
};

const items: NavItem[] = [
  { to: '/', label: 'Главная', icon: LayoutGrid, end: true },
  { to: '/places', label: 'Точки', icon: MapPin },
  { to: '/reviews', label: 'Отзывы', icon: MessageSquare },
  { to: '/subscription', label: 'Подписка', icon: Wallet },
  { to: '/profile', label: 'Профиль', icon: User },
];

/**
 * Layout с боковой навигацией на десктопе и плавающим bottom-nav на мобиле.
 * Kvell-style: щедрый paddings, мягкие тени, ровно один акцентный цвет.
 */
export function AppShell() {
  return (
    <div className="min-h-screen bg-canvas">
      <div className="mx-auto flex max-w-shell flex-col lg:flex-row">
        <Sidebar />
        <main className="flex-1 pb-28 pt-4 sm:pt-6 lg:pb-10">
          <div className="px-4 sm:px-6 lg:px-10">
            <Outlet />
          </div>
        </main>
      </div>
      <BottomNav />
    </div>
  );
}

function Sidebar() {
  return (
    <aside className="hidden w-64 shrink-0 px-4 py-8 lg:block">
      <div className="sticky top-8">
        <div className="px-3 pb-8">
          <span className="text-xl font-semibold tracking-tight">Guard Reviews</span>
          <p className="mt-1 text-xs text-ink-500">Кабинет владельца</p>
        </div>
        <nav className="space-y-1">
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) =>
                [
                  'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                  isActive
                    ? 'bg-accent-soft text-accent'
                    : 'text-ink-700 hover:bg-ink-100',
                ].join(' ')
              }
            >
              <item.icon className="h-5 w-5" />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
      </div>
    </aside>
  );
}

function BottomNav() {
  return (
    <nav
      className="fixed inset-x-3 bottom-3 z-30 flex justify-around rounded-2xl bg-surface
        px-2 py-2 shadow-lift lg:hidden"
      style={{ paddingBottom: `calc(0.5rem + env(safe-area-inset-bottom))` }}
    >
      {items.map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          end={item.end}
          className={({ isActive }) =>
            [
              'flex flex-1 flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-[11px] font-medium transition',
              isActive ? 'text-accent' : 'text-ink-500',
            ].join(' ')
          }
        >
          <item.icon className="h-5 w-5" />
          <span>{item.label}</span>
        </NavLink>
      ))}
    </nav>
  );
}
