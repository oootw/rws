import { Link } from 'react-router-dom';
import { MapPin, Plus } from 'lucide-react';

import { PlaceCard, useOwnerPlacesQuery } from '@/entities/place';
import { Card, EmptyState, Skeleton } from '@/shared/ui';

const SKELETON_TILES = 4;

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

      {places.isPending && (
        <div className="grid gap-3 sm:grid-cols-2" aria-busy="true" aria-live="polite">
          {Array.from({ length: SKELETON_TILES }).map((_, index) => (
            <Card key={index} className="space-y-3">
              <Skeleton className="h-5 w-1/2" />
              <Skeleton className="h-4 w-3/4" />
              <Skeleton className="h-4 w-1/3" />
            </Card>
          ))}
        </div>
      )}

      {places.data !== undefined && places.data.length === 0 && (
        <EmptyState
          icon={<MapPin className="h-8 w-8" />}
          title="Пока нет точек"
          description="Нажмите «Добавить точку» или используйте команду /addplace в Telegram-боте."
        />
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
