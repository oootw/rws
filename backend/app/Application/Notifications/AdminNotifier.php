<?php

declare(strict_types=1);

namespace App\Application\Notifications;

/**
 * Канал тревог в адрес команды/основателя.
 * Не путать с OwnerNotifier — этот достучится до фиксированного e-mail
 * команды, независимо от конкретного владельца.
 */
interface AdminNotifier
{
    public function alert(string $subject, string $body): void;
}
