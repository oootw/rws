<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Domain\Iam\OwnerId;
use App\Domain\Iam\OwnerRepository;
use App\Interface\Http\Views\Owner\OwnerMeView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OwnerMeController
{
    public function __construct(
        private readonly OwnerRepository $owners,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('owner');

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $owner = $this->owners->findById(new OwnerId((string) $user->id));

        if ($owner === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json(['data' => OwnerMeView::fromOwner($owner)]);
    }
}
