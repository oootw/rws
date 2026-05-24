<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Channels;

use App\Application\Notifications\Channels\NotificationChannel;
use App\Application\Notifications\OwnerNotification;
use App\Mail\PlainTextMail;
use Illuminate\Contracts\Mail\Factory as MailFactory;

final readonly class EmailNotificationChannel implements NotificationChannel
{
    public function __construct(
        private MailFactory $mailer,
    ) {}

    public function supports(OwnerNotification $notification): bool
    {
        return $notification->contact->email !== null;
    }

    public function deliver(OwnerNotification $notification): void
    {
        $this->mailer
            ->mailer()
            ->to($notification->contact->email)
            ->send(new PlainTextMail($notification->emailSubject, $notification->text));
    }
}
