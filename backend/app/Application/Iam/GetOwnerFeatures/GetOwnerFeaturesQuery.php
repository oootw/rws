<?php

declare(strict_types=1);

namespace App\Application\Iam\GetOwnerFeatures;

final readonly class GetOwnerFeaturesQuery
{
    public function __construct(public string $ownerId) {}
}
