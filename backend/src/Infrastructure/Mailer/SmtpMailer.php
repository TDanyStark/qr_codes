<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
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
            'from' => $settings?->get('mail.from') ?? getenv('MAILER_USERNAME') ?: 'noreply@example.com',
            'timeout' => (int) ($settings?->get('mail.timeout') ?? getenv('MAILER_TIMEOUT') ?? 10),
            'debug' => (bool) ($settings?->get('mail.debug') ?? getenv('MAIL_DEBUG') ?: false),
        ];
    }

    public function send(string $to, string $subject, string $body, array $headers = []): void
    {
        $mail = new PHPMailer(true);

        $debugOutput = '';
        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            if (!empty($this->config['port'])) {
                $mail->Port = (int)$this->config['port'];
            }
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            // Configure encryption using PHPMailer constants.
            $encryption = strtolower((string)($this->config['encryption'] ?? ''));
            if ($encryption === 'ssl' || (int)$this->config['port'] === 465) {
                // Implicit SSL (SMTPS) - typically port 465
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false; // do not attempt STARTTLS
            } elseif ($encryption === 'tls' || (int)$this->config['port'] === 587) {
                // Explicit TLS via STARTTLS
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
            } else {
                // No encryption
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }
            // timeout
            if (!empty($this->config['timeout'])) {
                $mail->Timeout = (int)$this->config['timeout'];
            }
            // Common option: allow configuring TLS context (eg to allow self-signed in dev)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                ],
            ];
            // debug capture - use PHPMailer SMTP debug level
            if (!empty($this->config['debug'])) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                // Capture debug output into a variable for logging
                $mail->Debugoutput = function ($str, $level) use (&$debugOutput) {
                    $debugOutput .= trim($str) . "\n";
                };
            }
            $mail->setFrom($this->config['from']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
        } catch (PHPMailerException $e) {
            // include debug output if present for diagnosis, but avoid logging passwords
            $meta = ['error' => $e->getMessage(), 'host' => $this->config['host'], 'port' => $this->config['port']];
            if (!empty($debugOutput)) {
                $meta['smtp_debug'] = $debugOutput;
            }
            $this->logger->error('SmtpMailer error', $meta);
            throw new MailException('SMTP send failed: ' . $e->getMessage());
        }
    }
}
