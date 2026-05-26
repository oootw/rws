<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use DomainException;

final class LoginCodeExpired extends DomainException {}
