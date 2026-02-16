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

    public function send(string $to, string $subject, string $body, array $headers = [], array $attachments = []): void
    {
        $headersStr = '';
        $allHeaders = array_merge(['From' => $this->from], $headers);
        foreach ($allHeaders as $k => $v) {
            $headersStr .= "$k: $v\r\n";
        }

        if ($this->driver === 'mail') {
            $normalizedBody = preg_replace('/\r\n|\r|\n/', "\r\n", $body ?? '');
            if (!empty($attachments)) {
                $boundary = '=_QRCODE_' . bin2hex(random_bytes(12));
                $headersStr .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

                $message = "--{$boundary}\r\n";
                $message .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
                $message .= $normalizedBody . "\r\n";

                foreach ($attachments as $attachment) {
                    $filename = $attachment['filename'] ?? 'attachment.dat';
                    $mime = $attachment['mime'] ?? 'application/octet-stream';
                    $content = $attachment['content'] ?? '';
                    $encoded = chunk_split(base64_encode($content));

                    $message .= "--{$boundary}\r\n";
                    $message .= "Content-Type: {$mime}; name=\"{$filename}\"\r\n";
                    $message .= "Content-Transfer-Encoding: base64\r\n";
                    $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
                    $message .= $encoded . "\r\n";
                }
                $message .= "--{$boundary}--\r\n";

                $ok = @mail($to, $subject, $message, $headersStr);
            } else {
                $headersStr .= "Content-Type: text/plain; charset=utf-8\r\n";
                // Use PHP mail()
                $ok = @mail($to, $subject, $normalizedBody, $headersStr);
            }
            if ($ok === false) {
                $this->logger->error('BasicMailer: mail() returned false', ['to' => $to, 'subject' => $subject]);
                throw new MailException('Unable to send mail using mail()');
            }
            $this->logger->info('BasicMailer: email sent (mail())', ['to' => $to, 'subject' => $subject]);
            return;
        }

        // default: log the email (safe for dev)
        $attachmentNames = array_map(function ($attachment) {
            return $attachment['filename'] ?? 'attachment.dat';
        }, $attachments);
        $this->logger->info('BasicMailer: (log) sending email', [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachmentNames,
        ]);
    }
}
