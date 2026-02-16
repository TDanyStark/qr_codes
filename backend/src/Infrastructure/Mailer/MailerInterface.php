<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

interface MailerInterface
{
    /**
     * Send an email.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $headers Additional headers as key=>value
     * @return void
     * @throws MailException on failure
     */
    /**
     * @param array<int, array{filename: string, content: string, mime?: string}> $attachments
     */
    public function send(string $to, string $subject, string $body, array $headers = [], array $attachments = []): void;
}
