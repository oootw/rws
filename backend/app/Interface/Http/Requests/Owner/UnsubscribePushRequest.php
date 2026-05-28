<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class UnsubscribePushRequest extends FormRequest
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
            'endpoint' => ['required', 'string', 'url', 'max:2048', 'starts_with:https://'],
        ];
    }
}
