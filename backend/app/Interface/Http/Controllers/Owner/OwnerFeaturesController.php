<?php

declare(strict_types=1);

namespace App\Interface\Http\Controllers\Owner;

use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesHandler;
use App\Application\Iam\GetOwnerFeatures\GetOwnerFeaturesQuery;
use App\Domain\Iam\Feature;
use App\Interface\Http\Controllers\Owner\Support\CurrentOwnerId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class OwnerFeaturesController
{
    public function __construct(
        private GetOwnerFeaturesHandler $features,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $ownerId = CurrentOwnerId::fromRequest($request);

        $granted = $this->features->handle(new GetOwnerFeaturesQuery(ownerId: $ownerId->value));

        return response()->json([
            'data' => [
                'features' => array_map(static fn (Feature $f) => $f->value, $granted),
            ],
        ]);
    }
}
