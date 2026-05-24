<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCriticalErrorRequest extends FormRequest
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
        return [
            'context' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'context.required' => 'Не указан контекст ошибки.',
            'context.max' => 'Контекст ошибки слишком длинный.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'context' => 'контекст',
        ];
    }
}
