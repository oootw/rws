<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Application\Notifications\AdminNotifier;
use App\Mail\PlainTextMail;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Factory as MailFactory;

final readonly class EmailAdminNotifier implements AdminNotifier
{
    public function __construct(
        private MailFactory $mailer,
        private Repository $config,
    ) {}

    public function alert(string $subject, string $body): void
    {
        $email = $this->config->get('guardreviews.admin_alert_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        $this->mailer
            ->mailer()
            ->to($email)
            ->send(new PlainTextMail($subject, $body));
    }
}
