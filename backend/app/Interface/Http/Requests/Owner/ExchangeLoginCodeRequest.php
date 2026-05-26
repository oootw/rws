<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use App\Application\Iam\ExchangeOwnerLoginCode\ExchangeOwnerLoginCodeCommand;
use Illuminate\Foundation\Http\FormRequest;

final class ExchangeLoginCodeRequest extends FormRequest
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
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ];
    }

    public function toCommand(): ExchangeOwnerLoginCodeCommand
    {
        return new ExchangeOwnerLoginCodeCommand(code: (string) $this->input('code'));
    }
}
