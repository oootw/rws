import { useEffect } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useQueryClient } from '@tanstack/react-query';

import { useSessionQuery } from '@/entities/session';
import { useFeaturesQuery } from '@/entities/features';

/**
 * Guard для всех маршрутов, требующих аутентификации.
 * Использует useSessionQuery — никаких прямых fetch'ей.
 *
 * Помимо guard'а — параллельно запускает useFeaturesQuery, чтобы кэш фич
 * был тёплым к моменту первого `<FeatureGate>`. Без этого префетча на первой
 * отрисовке gated-UI скрыт (useFeature возвращает false до загрузки).
 */
export function RequireSession() {
  const session = useSessionQuery();
  const features = useFeaturesQuery();
  const location = useLocation();
  const queryClient = useQueryClient();

  // При логауте — выкинуть фичи из кэша, чтобы следующий owner получил свои.
  const isLoggedIn = session.data !== null && session.data !== undefined;
  useEffect(() => {
    if (!isLoggedIn) {
      queryClient.removeQueries({ queryKey: ['features'] });
    }
  }, [isLoggedIn, queryClient]);

  if (session.isPending) {
    return (
      <div className="flex min-h-screen items-center justify-center text-sm text-ink-500">
        Загружаем сессию…
      </div>
    );
  }

  if (!isLoggedIn) {
    return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  }

  // Не блокируем рендер из-за фич — gated UI просто покажет fallback пока кеш
  // не загрузится; основной контент должен быть видим сразу.
  void features;

  return <Outlet />;
}
