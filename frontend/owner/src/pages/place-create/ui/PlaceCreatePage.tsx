import { useNavigate, Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { isAxiosError } from '@/shared/api';

import { usePlaceChargePreviewQuery } from '@/entities/place';
import { ChargePreviewBanner, useCreatePlaceMutation } from '@/features/create-place';
import { FeatureGate, UpsellCard } from '@/entities/features';
import { PlaceForm } from '@/widgets/place-form';

const errorMessageFor = (error: unknown): string | null => {
  if (error === null) return null;
  if (isAxiosError(error)) {
    return error.response?.data?.message ?? 'Не удалось сохранить точку.';
  }
  return 'Не удалось сохранить точку.';
};

export function PlaceCreatePage() {
  const navigate = useNavigate();
  const preview = usePlaceChargePreviewQuery();
  const create = useCreatePlaceMutation();

  const backLink = (
    <Link to="/places" className="inline-flex items-center gap-2 text-sm text-ink-500 hover:text-ink-900">
      <ArrowLeft className="h-4 w-4" />
      К списку точек
    </Link>
  );

  return (
    <div className="space-y-6">
      {backLink}

      <header className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">Новая точка</h1>
        <p className="text-sm text-ink-500">Заполните данные и сохраните.</p>
      </header>

      <FeatureGate
        feature="multiple_places"
        fallback={
          <UpsellCard
            title="Добавление точек доступно в платных тарифах"
            description="На текущем тарифе создание новых точек выключено. Обновите подписку, чтобы открыть фичу."
          />
        }
      >
        <ChargePreviewBanner charge={preview.data} />

        <PlaceForm
          submitLabel="Создать точку"
          isPending={create.isPending}
          errorMessage={create.isError ? errorMessageFor(create.error) : null}
          onSubmit={(input) =>
            create.mutate(input, {
              onSuccess: ({ data }) => navigate(`/places/${data.id}`, { replace: true }),
            })
          }
        />
      </FeatureGate>
    </div>
  );
}
