<?php

declare(strict_types=1);

namespace App\Application\Iam\Exceptions;

use RuntimeException;

final class TelegramChatNotOwnedByCaller extends RuntimeException {}
