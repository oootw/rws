import { useState } from 'react';
import type { FormEvent } from 'react';
import { Plus, Trash2 } from 'lucide-react';

import type { PlaceInput, PlacePlatform } from '@/entities/place';
import { Button, Card, Input } from '@/shared/ui';

type PlaceFormProps = {
  initial?: PlaceInput;
  submitLabel: string;
  isPending: boolean;
  errorMessage?: string | null;
  onSubmit: (input: PlaceInput) => void;
};

const platformTypeOptions = [
  { value: '2gis', label: '2GIS' },
  { value: 'yandex', label: 'Яндекс.Карты' },
  { value: 'custom', label: 'Своя ссылка' },
] as const;

const emptyPlatform = (): PlacePlatform => ({ type: '2gis', url: '', label: '2GIS' });

const initialFromProps = (initial: PlaceInput | undefined): PlaceInput =>
  initial ?? {
    title: '',
    background_image_url: null,
    platforms: [emptyPlatform()],
  };

export function PlaceForm({ initial, submitLabel, isPending, errorMessage, onSubmit }: PlaceFormProps) {
  const [state, setState] = useState<PlaceInput>(initialFromProps(initial));

  const updatePlatform = (index: number, patch: Partial<PlacePlatform>): void => {
    setState((prev) => ({
      ...prev,
      platforms: prev.platforms.map((p, i) => (i === index ? { ...p, ...patch } : p)),
    }));
  };

  const removePlatform = (index: number): void => {
    setState((prev) => ({
      ...prev,
      platforms: prev.platforms.filter((_, i) => i !== index),
    }));
  };

  const addPlatform = (): void => {
    setState((prev) => ({ ...prev, platforms: [...prev.platforms, emptyPlatform()] }));
  };

  const submit = (event: FormEvent<HTMLFormElement>): void => {
    event.preventDefault();
    onSubmit({
      ...state,
      background_image_url:
        state.background_image_url !== null && state.background_image_url.trim() !== ''
          ? state.background_image_url.trim()
          : null,
      platforms: state.platforms.filter((p) => p.url.trim() !== ''),
    });
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <Card className="space-y-4">
        <label className="block space-y-1">
          <span className="text-sm font-medium text-ink-700">Название</span>
          <Input
            value={state.title}
            onChange={(event) => setState((prev) => ({ ...prev, title: event.target.value }))}
            required
            maxLength={255}
            placeholder="Уютное кафе"
          />
        </label>

        <label className="block space-y-1">
          <span className="text-sm font-medium text-ink-700">URL фонового изображения</span>
          <Input
            value={state.background_image_url ?? ''}
            onChange={(event) =>
              setState((prev) => ({
                ...prev,
                background_image_url: event.target.value === '' ? null : event.target.value,
              }))
            }
            placeholder="https://… (опционально)"
            type="url"
          />
        </label>
      </Card>

      <Card className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-base font-semibold">Площадки</h3>
          <Button type="button" variant="ghost" onClick={addPlatform}>
            <Plus className="h-4 w-4" />
            Добавить
          </Button>
        </div>

        {state.platforms.length === 0 ? (
          <p className="text-sm text-ink-500">
            Без хотя бы одной площадки точка не сможет принимать положительные отзывы.
          </p>
        ) : (
          <div className="space-y-3">
            {state.platforms.map((platform, index) => (
              <div key={index} className="grid gap-2 sm:grid-cols-[140px_1fr_140px_auto]">
                <select
                  className="rounded-xl border border-ink-200 bg-surface px-3 py-2 text-sm"
                  value={platform.type}
                  onChange={(event) =>
                    updatePlatform(index, {
                      type: event.target.value,
                      label:
                        platformTypeOptions.find((opt) => opt.value === event.target.value)?.label
                        ?? platform.label,
                    })
                  }
                >
                  {platformTypeOptions.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
                <Input
                  value={platform.url}
                  onChange={(event) => updatePlatform(index, { url: event.target.value })}
                  placeholder="https://…"
                  type="url"
                />
                <Input
                  value={platform.label}
                  onChange={(event) => updatePlatform(index, { label: event.target.value })}
                  placeholder="Подпись кнопки"
                  maxLength={120}
                />
                <Button
                  type="button"
                  variant="ghost"
                  className="text-danger"
                  onClick={() => removePlatform(index)}
                  aria-label="Удалить площадку"
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            ))}
          </div>
        )}
      </Card>

      {errorMessage !== null && errorMessage !== undefined && (
        <p role="alert" className="text-sm text-danger">
          {errorMessage}
        </p>
      )}

      <div className="flex justify-end gap-2">
        <Button type="submit" disabled={isPending}>
          {isPending ? 'Сохраняем…' : submitLabel}
        </Button>
      </div>
    </form>
  );
}
