import { Route, Routes } from 'react-router-dom';

import { AppShell } from '@/widgets/app-shell';
import { AuthShell } from '@/widgets/auth-shell';
import { DashboardPage } from '@/pages/dashboard';
import { LoginPage } from '@/pages/login';
import { NotFoundPage } from '@/pages/not-found';
import { PlaceholderPage } from '@/pages/placeholder';
import { PlaceCreatePage } from '@/pages/place-create';
import { PlaceDetailPage } from '@/pages/place-detail';
import { PlaceEditPage } from '@/pages/place-edit';
import { PlacesListPage } from '@/pages/places-list';
import { ReviewsListPage } from '@/pages/reviews-list';

import { RequireSession } from './RequireSession';

export function AppRouter() {
  return (
    <Routes>
      <Route element={<AuthShell />}>
        <Route path="/login" element={<LoginPage />} />
      </Route>

      <Route element={<RequireSession />}>
        <Route element={<AppShell />}>
          <Route path="/" element={<DashboardPage />} />
          <Route path="/places" element={<PlacesListPage />} />
          <Route path="/places/new" element={<PlaceCreatePage />} />
          <Route path="/places/:placeId" element={<PlaceDetailPage />} />
          <Route path="/places/:placeId/edit" element={<PlaceEditPage />} />
          <Route path="/reviews" element={<ReviewsListPage />} />
          <Route path="/subscription" element={<PlaceholderPage title="Подписка" />} />
          <Route path="/profile" element={<PlaceholderPage title="Профиль" />} />
        </Route>
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}
