import { useNavigate, useParams, Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';

import { usePlaceDetailQuery } from '@/entities/place';
import { useUpdatePlaceMutation } from '@/features/update-place';
import { PlaceForm } from '@/widgets/place-form';
import { Card } from '@/shared/ui';
import { isAxiosError } from '@/shared/api';

const errorMessageFor = (error: unknown): string | null => {
  if (error === null) return null;
  if (isAxiosError(error)) {
    return error.response?.data?.message ?? 'Не удалось сохранить точку.';
  }
  return 'Не удалось сохранить точку.';
};

export function PlaceEditPage() {
  const { placeId } = useParams<{ placeId: string }>();
  const navigate = useNavigate();
  const detail = usePlaceDetailQuery(placeId);
  const update = useUpdatePlaceMutation();

  return (
    <div className="space-y-6">
      <Link
        to={placeId ? `/places/${placeId}` : '/places'}
        className="inline-flex items-center gap-2 text-sm text-ink-500 hover:text-ink-900"
      >
        <ArrowLeft className="h-4 w-4" />
        К точке
      </Link>

      <header className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Редактирование</h1>
      </header>

      {detail.isPending && <Card className="text-sm text-ink-500">Загружаем точку…</Card>}

      {detail.isError && <Card className="text-sm text-danger">Не удалось загрузить точку.</Card>}

      {detail.data !== undefined && placeId !== undefined && (
        <PlaceForm
          initial={{
            title: detail.data.title,
            background_image_url: detail.data.background_image_url,
            platforms: detail.data.platforms,
          }}
          submitLabel="Сохранить"
          isPending={update.isPending}
          errorMessage={update.isError ? errorMessageFor(update.error) : null}
          onSubmit={(input) =>
            update.mutate(
              { placeId, input },
              { onSuccess: () => navigate(`/places/${placeId}`, { replace: true }) },
            )
          }
        />
      )}
    </div>
  );
}
