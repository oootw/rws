import { Link } from 'react-router-dom';
import { Plus } from 'lucide-react';

import { PlaceCard, useOwnerPlacesQuery } from '@/entities/place';
import { Card } from '@/shared/ui';

export function PlacesListPage() {
  const places = useOwnerPlacesQuery();

  return (
    <div className="space-y-6">
      <header className="flex flex-wrap items-end justify-between gap-3">
        <div className="space-y-1">
          <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Точки</h1>
          <p className="text-sm text-ink-500">Список ваших заведений и их QR-коды.</p>
        </div>
        <Link
          to="/places/new"
          className="inline-flex items-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-medium text-accent-fg transition hover:brightness-105"
        >
          <Plus className="h-4 w-4" />
          Добавить точку
        </Link>
      </header>

      {places.isPending && <Card className="text-sm text-ink-500">Загружаем точки…</Card>}

      {places.data !== undefined && places.data.length === 0 && (
        <Card className="text-sm text-ink-500">
          У вас пока нет точек. Нажмите «Добавить точку» или используйте команду /addplace в Telegram-боте.
        </Card>
      )}

      {places.data !== undefined && places.data.length > 0 && (
        <div className="grid gap-3 sm:grid-cols-2">
          {places.data.map((place) => (
            <Link key={place.id} to={`/places/${place.id}`} className="block">
              <PlaceCard place={place} />
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
