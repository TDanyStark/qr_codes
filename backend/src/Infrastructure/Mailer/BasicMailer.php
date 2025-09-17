<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;

class BasicMailer implements MailerInterface
{
    private LoggerInterface $logger;
    private string $driver;
    private string $from;

    public function __construct(LoggerInterface $logger, ?SettingsInterface $settings = null)
    {
        $this->logger = $logger;

        if ($settings) {
            $this->driver = (string) ($settings->get('mail.driver') ?? getenv('MAIL_DRIVER') ?: 'log');
            $this->from = (string) ($settings->get('mail.from') ?? getenv('MAIL_FROM') ?: 'noreply@example.com');
        } else {
            $this->driver = getenv('MAIL_DRIVER') ?: 'log';
            $this->from = getenv('MAIL_FROM') ?: 'noreply@example.com';
        }
    }

    public function send(string $to, string $subject, string $body, array $headers = []): void
    {
        $headersStr = '';
        $allHeaders = array_merge(['From' => $this->from, 'Content-Type' => 'text/plain; charset=utf-8'], $headers);
        foreach ($allHeaders as $k => $v) {
            $headersStr .= "$k: $v\r\n";
        }

        if ($this->driver === 'mail') {
            // Use PHP mail()
            $ok = @mail($to, $subject, $body, $headersStr);
            if ($ok === false) {
                $this->logger->error('BasicMailer: mail() returned false', ['to' => $to, 'subject' => $subject]);
                throw new MailException('Unable to send mail using mail()');
            }
            $this->logger->info('BasicMailer: email sent (mail())', ['to' => $to, 'subject' => $subject]);
            return;
        }

        // default: log the email (safe for dev)
        $this->logger->info('BasicMailer: (log) sending email', ['to' => $to, 'subject' => $subject, 'body' => $body]);
    }
}
