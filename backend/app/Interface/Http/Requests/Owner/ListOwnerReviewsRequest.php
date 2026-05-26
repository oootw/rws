<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use App\Application\Reviews\ListOwnerReviews\ListOwnerReviewsQuery;
use App\Application\Reviews\ListOwnerReviews\OwnerReviewFilters;
use App\Enums\ReviewStatus;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListOwnerReviewsRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(ReviewStatus::class)],
            'place_id' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toQuery(string $ownerId): ListOwnerReviewsQuery
    {
        return new ListOwnerReviewsQuery(
            ownerId: $ownerId,
            filters: new OwnerReviewFilters(
                status: $this->input('status') === null
                    ? null
                    : ReviewStatus::from((string) $this->input('status')),
                placeId: $this->input('place_id'),
                from: $this->dateInput('from'),
                until: $this->dateInput('until'),
            ),
            page: (int) ($this->input('page') ?? 1),
            perPage: (int) ($this->input('per_page') ?? 20),
        );
    }

    private function dateInput(string $key): ?DateTimeImmutable
    {
        $raw = $this->input($key);

        return $raw === null ? null : new DateTimeImmutable((string) $raw);
    }
}
