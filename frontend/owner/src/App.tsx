import { Route, Routes } from 'react-router-dom';

import { AppShell } from '@/layouts/AppShell';
import { AuthLayout } from '@/layouts/AuthLayout';
import { DashboardPage } from '@/features/dashboard/DashboardPage';
import { LoginPage } from '@/features/auth/LoginPage';
import { NotFoundPage } from '@/features/system/NotFoundPage';
import { PlaceholderPage } from '@/features/system/PlaceholderPage';

/**
 * Маршруты Фазы 0 — каркас.
 * Реальная авторизация и API подключатся в Фазе 1+.
 */
export function App() {
  return (
    <Routes>
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<LoginPage />} />
      </Route>

      <Route element={<AppShell />}>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/places" element={<PlaceholderPage title="Точки" />} />
        <Route path="/reviews" element={<PlaceholderPage title="Отзывы" />} />
        <Route path="/subscription" element={<PlaceholderPage title="Подписка" />} />
        <Route path="/profile" element={<PlaceholderPage title="Профиль" />} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
