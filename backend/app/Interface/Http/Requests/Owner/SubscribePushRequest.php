<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class SubscribePushRequest extends FormRequest
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
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'user_agent' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'endpoint.starts_with' => 'Push endpoint должен быть https URL.',
            'endpoint.url' => 'Push endpoint должен быть валидным URL.',
        ];
    }
}
