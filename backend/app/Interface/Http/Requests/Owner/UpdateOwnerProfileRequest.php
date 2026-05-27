<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOwnerProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'subdomain' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9](?:[a-z0-9-]{1,30}[a-z0-9])?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'email.email' => 'Неверный формат email.',
            'subdomain.regex' => 'Адрес должен содержать 3–32 символа: латиница, цифры и дефис.',
        ];
    }
}
