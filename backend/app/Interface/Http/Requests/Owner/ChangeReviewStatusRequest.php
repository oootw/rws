<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Owner;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeReviewStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(ReviewStatus::class)],
        ];
    }

    public function status(): ReviewStatus
    {
        return ReviewStatus::from((string) $this->input('status'));
    }
}
