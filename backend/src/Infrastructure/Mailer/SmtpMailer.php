<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;

class SmtpMailer implements MailerInterface
{
    private LoggerInterface $logger;
    private array $config;

    public function __construct(LoggerInterface $logger, ?SettingsInterface $settings = null)
    {
        $this->logger = $logger;
        $this->config = [
            'host' => $settings?->get('mail.host') ?? getenv('MAILER_HOST') ?: getenv('MAIL_HOST'),
            'port' => $settings?->get('mail.port') ?? getenv('MAILER_PORT') ?: getenv('MAIL_PORT'),
            'username' => $settings?->get('mail.username') ?? getenv('MAILER_USERNAME') ?: getenv('MAIL_USERNAME'),
            'password' => $settings?->get('mail.password') ?? getenv('MAILER_PASSWORD') ?: getenv('MAIL_PASSWORD'),
            'encryption' => $settings?->get('mail.encryption') ?? getenv('MAILER_ENCRYPTION') ?? getenv('MAIL_ENCRYPTION') ?? 'tls',
            'from' => $settings?->get('mail.from') ?? getenv('MAIL_FROM') ?: 'noreply@example.com',
        ];
    }

    public function send(string $to, string $subject, string $body, array $headers = []): void
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            if (!empty($this->config['port'])) {
                $mail->Port = (int)$this->config['port'];
            }
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->setFrom($this->config['from']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            $this->logger->info('SmtpMailer: email sent', ['to' => $to, 'subject' => $subject]);
        } catch (PHPMailerException $e) {
            $this->logger->error('SmtpMailer error', ['error' => $e->getMessage()]);
            throw new MailException('SMTP send failed: ' . $e->getMessage());
        }
    }
}
