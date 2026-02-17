<?php

declare(strict_types=1);

namespace App\Application\Services\Reporting;

use App\Domain\QrCode\QrCodeRepository;
use App\Domain\QrSubscription\QrSubscriptionRepository;
use App\Domain\ReportSettings\ReportSettings;
use App\Domain\ReportSettings\ReportSettingsRepository;
use App\Domain\Scan\ScanRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;

class ReportNotificationService
{
    private LoggerInterface $logger;

    private ScanRepository $scanRepository;

    private QrCodeRepository $qrCodeRepository;

    private UserRepository $userRepository;

    private QrSubscriptionRepository $subscriptionRepository;

    private ReportSettingsRepository $settingsRepository;

    private MailerInterface $mailer;

    public function __construct(
        LoggerInterface $logger,
        ScanRepository $scanRepository,
        QrCodeRepository $qrCodeRepository,
        UserRepository $userRepository,
        QrSubscriptionRepository $subscriptionRepository,
        ReportSettingsRepository $settingsRepository,
        MailerInterface $mailer
    ) {
        $this->logger = $logger;
        $this->scanRepository = $scanRepository;
        $this->qrCodeRepository = $qrCodeRepository;
        $this->userRepository = $userRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->settingsRepository = $settingsRepository;
        $this->mailer = $mailer;
    }

    public function runDueReports(?\DateTimeImmutable $nowUtc = null): int
    {
        $nowUtc = $nowUtc ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $settings = $this->settingsRepository->getActive();
        if ($settings === null || !$settings->isActive()) {
            $this->logger->info('ReportNotificationService: no active settings');
            return 0;
        }

        $timezone = new \DateTimeZone($settings->getTimezone() ?: 'UTC');
        $nowLocal = $nowUtc->setTimezone($timezone);
        $scheduledAt = $this->getScheduledDateTime($settings, $nowLocal);

        if ($scheduledAt === null) {
            $this->logger->info('ReportNotificationService: schedule not due');
            return 0;
        }

        if ($nowLocal < $scheduledAt) {
            $this->logger->info('ReportNotificationService: waiting for scheduled time');
            return 0;
        }

        $lastRunAt = $settings->getLastRunAt();
        if ($lastRunAt !== null && $lastRunAt->setTimezone($timezone) >= $scheduledAt) {
            $this->logger->info('ReportNotificationService: already ran for this period');
            return 0;
        }

        $range = $this->getReportRange($settings, $scheduledAt);
        $rangeStartUtc = $range['start']->setTimezone(new \DateTimeZone('UTC'));
        $rangeEndUtc = $range['end']->setTimezone(new \DateTimeZone('UTC'));

        $subscriptions = $this->subscriptionRepository->listAll();
        if (count($subscriptions) === 0) {
            $this->logger->info('ReportNotificationService: no subscriptions to process');
            $this->settingsRepository->updateLastRunAt((int)$settings->getId(), $nowUtc);
            return 0;
        }

        $subsByUser = [];
        foreach ($subscriptions as $subscription) {
            $subsByUser[$subscription->getUserId()][] = $subscription;
        }

        $this->logger->info('ReportNotificationService: subscriptions grouped', [
            'users' => array_keys($subsByUser),
            'total_subscriptions' => count($subscriptions),
        ]);

        $sentCount = 0;
        foreach ($subsByUser as $userId => $userSubscriptions) {
            try {
                $this->logger->info('ReportNotificationService: loading user', ['user_id' => $userId]);
                $user = $this->userRepository->findUserOfId((int)$userId);
            } catch (\Throwable $e) {
                $this->logger->warning('ReportNotificationService: user not found', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                ]);
                continue;
            }

            $attachments = [];
            $qrSummaries = [];

            foreach ($userSubscriptions as $subscription) {
                $qrId = $subscription->getQrCodeId();
                try {
                    $qr = $this->qrCodeRepository->findOfId($qrId);
                } catch (\Throwable $e) {
                    $this->logger->warning('ReportNotificationService: QR not found', ['qr_id' => $qrId]);
                    continue;
                }

                $periodCount = $this->scanRepository->countInRange($qrId, $rangeStartUtc, $rangeEndUtc);
                $totalCount = $this->scanRepository->totalCount($qrId);

                $qrName = $qr->getName() ?: 'QR #' . $qrId;

                $scans = $this->scanRepository->findByQrCodeInRange($qrId, $rangeStartUtc, $rangeEndUtc, 10000);
                $csv = $this->buildCsv($scans);

                $filename = sprintf(
                    'qrcode_%d_scans_%s_%s.csv',
                    $qrId,
                    $range['start']->format('Ymd'),
                    $range['end']->format('Ymd')
                );

                $attachments[] = [
                    'filename' => $filename,
                    'content' => $csv,
                    'mime' => 'text/csv',
                ];

                $qrSummaries[] = [
                    'id' => $qrId,
                    'name' => $qrName,
                    'total' => $totalCount,
                    'period' => $periodCount,
                ];
            }

            if (count($qrSummaries) === 0) {
                continue;
            }

            $subject = $this->buildSubject($settings, $range['start'], $range['end']);
            $body = $this->buildBody($user->getName(), $qrSummaries, $settings->getLookerUrl(), $range['start'], $range['end']);

            try {
                $this->mailer->send($user->getEmail(), $subject, $body, [], $attachments);
                $sentCount++;
            } catch (\Throwable $e) {
                $this->logger->error('ReportNotificationService: failed to send report', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->settingsRepository->updateLastRunAt((int)$settings->getId(), $nowUtc);

        return $sentCount;
    }

    private function getScheduledDateTime(ReportSettings $settings, \DateTimeImmutable $nowLocal): ?\DateTimeImmutable
    {
        [$hour, $minute, $second] = $this->parseTimeOfDay($settings->getTimeOfDay());

        if ($settings->getScheduleType() === 'weekly') {
            $dayOfWeek = $settings->getDayOfWeek();
            if ($dayOfWeek === null) {
                return null;
            }

            $currentN = (int)$nowLocal->format('N');
            $delta = $dayOfWeek - $currentN;
            $scheduled = $nowLocal->modify($delta . ' days')->setTime($hour, $minute, $second);

            return $scheduled;
        }

        $dayOfMonth = $settings->getDayOfMonth();
        if ($dayOfMonth === null) {
            return null;
        }

        $daysInMonth = (int)$nowLocal->format('t');
        $safeDay = min(max($dayOfMonth, 1), $daysInMonth);

        return $nowLocal->setDate(
            (int)$nowLocal->format('Y'),
            (int)$nowLocal->format('m'),
            $safeDay
        )->setTime($hour, $minute, $second);
    }

    /**
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable}
     */
    private function getReportRange(ReportSettings $settings, \DateTimeImmutable $scheduledAt): array
    {
        $periodEnd = $scheduledAt->setTime(0, 0, 0);

        if ($settings->getScheduleType() === 'weekly') {
            $periodStart = $periodEnd->modify('-7 days');
            return ['start' => $periodStart, 'end' => $periodEnd];
        }

        $periodStart = $periodEnd->modify('-1 month');
        return ['start' => $periodStart, 'end' => $periodEnd];
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function parseTimeOfDay(string $timeOfDay): array
    {
        $parts = explode(':', $timeOfDay);
        $hour = (int)($parts[0] ?? 0);
        $minute = (int)($parts[1] ?? 0);
        $second = (int)($parts[2] ?? 0);
        return [$hour, $minute, $second];
    }

    /**
     * @param array<int, array{id:int,name:string,total:int,period:int}> $qrSummaries
     */
    private function buildBody(string $userName, array $qrSummaries, ?string $lookerUrl, \DateTimeImmutable $start, \DateTimeImmutable $end): string
    {
        $rangeLabel = $start->format('Y-m-d') . ' a ' . $end->format('Y-m-d');
        $itemsHtml = '';
        foreach ($qrSummaries as $summary) {
            $itemsHtml .= '<li>' .
                '<strong>' . htmlspecialchars($summary['name']) . '</strong>' .
                ' - Total: ' . $summary['total'] .
                ', Periodo: ' . $summary['period'] .
                '</li>';
        }

        $lookerHtml = '';
        if (!empty($lookerUrl)) {
            $safeUrl = htmlspecialchars($lookerUrl);
            $lookerHtml = '<p>Dashboard interactivo: <a href="' . $safeUrl . '">' . $safeUrl . '</a></p>';
        }

        return '<p>Hola ' . htmlspecialchars($userName) . ',</p>' .
            '<p>Resumen de tus QRs para el periodo ' . $rangeLabel . ':</p>' .
            '<ul>' . $itemsHtml . '</ul>' .
            $lookerHtml .
            '<p>Se adjunta un CSV con el detalle de scans por QR.</p>';
    }

    private function buildSubject(ReportSettings $settings, \DateTimeImmutable $start, \DateTimeImmutable $end): string
    {
        $label = $settings->getScheduleType() === 'weekly' ? 'Semanal' : 'Mensual';
        return sprintf('Reporte %s de QRs (%s - %s)', $label, $start->format('Y-m-d'), $end->format('Y-m-d'));
    }

    /**
     * @param array<int, \App\Domain\Scan\Scan> $scans
     */
    private function buildCsv(array $scans): string
    {
        $headers = ['id', 'qrcode_id', 'scanned_at', 'ip', 'user_agent', 'city', 'country'];
        $lines = [implode(',', $headers)];

            foreach ($scans as $scan) {
                if (!is_object($scan) || !method_exists($scan, 'toArray')) {
                    continue;
            }
            $row = $scan->toArray();
                $escaped = array_map(function ($value) {
                    if ($value === null) {
                        return '';
                    }
                    $str = (string)$value;
                    $str = str_replace('"', '""', $str);
                    if (preg_match('/[",\n\r,]/', $str)) {
                        return '"' . $str . '"';
                }
                return $str;
            }, [$row['id'], $row['qrcode_id'], $row['scanned_at'], $row['ip'], $row['user_agent'], $row['city'], $row['country']]);

            $lines[] = implode(',', $escaped);
        }

        return implode("\r\n", $lines);
    }
}
