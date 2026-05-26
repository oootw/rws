<?php

declare(strict_types=1);

namespace App\Domain\Iam;

use DomainException;

final class LoginCodeAlreadyConsumed extends DomainException {}
