<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use App\Domain\Places\PlatformType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Базовый FormRequest для create/update точки: общая валидация title + platforms[].
 * Контроллеры передают `toArray()` далее в RegisterPlaceCommand / UpdatePlaceCommand.
 */
final class SavePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'background_image_url' => ['nullable', 'string', 'url', 'max:2048'],
            'platforms' => ['array'],
            'platforms.*.type' => ['required', Rule::enum(PlatformType::class)],
            'platforms.*.url' => ['required', 'string', 'url', 'max:2048'],
            'platforms.*.label' => ['required', 'string', 'max:120'],
        ];
    }

    /**
     * @return list<array{type: string, url: string, label: string}>
     */
    public function platformsPayload(): array
    {
        $raw = $this->input('platforms', []);

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $item): array => [
                'type' => (string) ($item['type'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'label' => (string) ($item['label'] ?? ''),
            ],
            $raw,
        ));
    }
}
