import { Navigate, Outlet, useLocation } from 'react-router-dom';

import { useSessionQuery } from '@/entities/session';

/**
 * Guard для всех маршрутов, требующих аутентификации.
 * Использует useSessionQuery — никаких прямых fetch'ей.
 */
export function RequireSession() {
  const session = useSessionQuery();
  const location = useLocation();

  if (session.isPending) {
    return (
      <div className="flex min-h-screen items-center justify-center text-sm text-ink-500">
        Загружаем сессию…
      </div>
    );
  }

  if (session.data === null || session.data === undefined) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  return <Outlet />;
}
