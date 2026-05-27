<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use App\Application\Payments\ListOwnerPayments\ListOwnerPaymentsQuery;
use Illuminate\Foundation\Http\FormRequest;

final class ListOwnerPaymentsRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toQuery(string $ownerId): ListOwnerPaymentsQuery
    {
        return new ListOwnerPaymentsQuery(
            ownerId: $ownerId,
            page: (int) ($this->input('page') ?? 1),
            perPage: (int) ($this->input('per_page') ?? 20),
        );
    }
}
