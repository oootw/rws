<?php

namespace App\Http\Requests\Public;

use App\Domain\Places\Place;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Place|null $place */
        $place = $this->attributes->get('resolved_place');

        if (! $place instanceof Place) {
            return [
                'platform_type' => ['required', 'string'],
            ];
        }

        $platformTypes = array_map(
            static fn ($platform): string => $platform->type->value,
            $place->platforms(),
        );

        return [
            'platform_type' => ['required', 'string', Rule::in($platformTypes)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'platform_type.required' => 'Выберите площадку для отзыва.',
            'platform_type.in' => 'Выбранная площадка недоступна.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'platform_type' => 'площадка',
        ];
    }
}
