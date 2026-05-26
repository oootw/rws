<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class TogglePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }

    public function active(): bool
    {
        return (bool) $this->input('active');
    }
}
