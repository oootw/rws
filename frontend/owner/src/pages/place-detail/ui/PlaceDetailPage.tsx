import { useParams, Link } from 'react-router-dom';
import { ArrowLeft, ExternalLink, Pencil } from 'lucide-react';

import { usePlaceDetailQuery } from '@/entities/place';
import { DeletePlaceButton } from '@/features/delete-place';
import { PlaceActivationToggle } from '@/features/toggle-place-activation';
import { Card } from '@/shared/ui';

export function PlaceDetailPage() {
  const { placeId } = useParams<{ placeId: string }>();
  const detail = usePlaceDetailQuery(placeId);

  return (
    <div className="space-y-6">
      <Link to="/places" className="inline-flex items-center gap-2 text-sm text-ink-500 hover:text-ink-900">
        <ArrowLeft className="h-4 w-4" />
        К списку точек
      </Link>

      {detail.isPending && <Card className="text-sm text-ink-500">Загружаем точку…</Card>}

      {detail.isError && <Card className="text-sm text-danger">Не удалось загрузить точку.</Card>}

      {detail.data !== undefined && placeId !== undefined && (
        <>
          <header className="flex flex-wrap items-start justify-between gap-3">
            <div className="space-y-1">
              <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{detail.data.title}</h1>
              <p className="text-sm text-ink-500">
                {detail.data.is_active ? 'Точка активна' : 'Точка выключена'}
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <PlaceActivationToggle placeId={placeId} isActive={detail.data.is_active} />
              <Link
                to={`/places/${placeId}/edit`}
                className="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-ink-700 transition hover:bg-ink-100"
              >
                <Pencil className="h-4 w-4" />
                Редактировать
              </Link>
              <DeletePlaceButton placeId={placeId} placeTitle={detail.data.title} />
            </div>
          </header>

          <div className="grid gap-3 sm:grid-cols-2">
            <Card className="space-y-2">
              <h2 className="text-base font-semibold">QR и ссылка скана</h2>
              <img
                src={detail.data.qr_png_url}
                alt={`QR код «${detail.data.title}»`}
                className="mx-auto h-48 w-48 rounded-2xl bg-ink-100"
              />
              <a
                href={detail.data.scan_url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center justify-center gap-2 text-sm text-accent hover:underline"
              >
                {detail.data.scan_url}
                <ExternalLink className="h-3.5 w-3.5" />
              </a>
            </Card>

            <Card className="space-y-3">
              <h2 className="text-base font-semibold">Площадки</h2>
              {detail.data.platforms.length === 0 ? (
                <p className="text-sm text-ink-500">Площадки не настроены.</p>
              ) : (
                <ul className="space-y-2">
                  {detail.data.platforms.map((platform) => (
                    <li
                      key={platform.type + platform.url}
                      className="flex items-center justify-between gap-3"
                    >
                      <span className="text-sm">{platform.label}</span>
                      <a
                        href={platform.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-xs text-accent hover:underline"
                      >
                        Открыть
                        <ExternalLink className="h-3.5 w-3.5" />
                      </a>
                    </li>
                  ))}
                </ul>
              )}
            </Card>
          </div>
        </>
      )}
    </div>
  );
}
