<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\ReportSettings;

use App\Domain\ReportSettings\ReportSettings;
use App\Domain\ReportSettings\ReportSettingsRepository;
use PDO;
use Psr\Log\LoggerInterface;

class PdoReportSettingsRepository implements ReportSettingsRepository
{
    private PDO $pdo;

    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getActive(): ?ReportSettings
    {
        $stmt = $this->pdo->query('SELECT * FROM report_settings WHERE active = 1 ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function findById(int $id): ?ReportSettings
    {
        $stmt = $this->pdo->prepare('SELECT * FROM report_settings WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function save(ReportSettings $settings): ReportSettings
    {
        if ($settings->getId() === null) {
            $stmt = $this->pdo->prepare('INSERT INTO report_settings (schedule_type, day_of_month, day_of_week, time_of_day, timezone, looker_url, active, last_run_at) VALUES (:schedule_type, :day_of_month, :day_of_week, :time_of_day, :timezone, :looker_url, :active, :last_run_at)');
            $stmt->execute([
                'schedule_type' => $settings->getScheduleType(),
                'day_of_month' => $settings->getDayOfMonth(),
                'day_of_week' => $settings->getDayOfWeek(),
                'time_of_day' => $settings->getTimeOfDay(),
                'timezone' => $settings->getTimezone(),
                'looker_url' => $settings->getLookerUrl(),
                'active' => $settings->isActive() ? 1 : 0,
                'last_run_at' => $settings->getLastRunAt()?->format('Y-m-d H:i:s'),
            ]);

            $id = (int)$this->pdo->lastInsertId();

            return new ReportSettings(
                $id,
                $settings->getScheduleType(),
                $settings->getDayOfMonth(),
                $settings->getDayOfWeek(),
                $settings->getTimeOfDay(),
                $settings->getTimezone(),
                $settings->getLookerUrl(),
                $settings->isActive(),
                $settings->getLastRunAt(),
                new \DateTimeImmutable(),
                new \DateTimeImmutable()
            );
        }

        $stmt = $this->pdo->prepare('UPDATE report_settings SET schedule_type = :schedule_type, day_of_month = :day_of_month, day_of_week = :day_of_week, time_of_day = :time_of_day, timezone = :timezone, looker_url = :looker_url, active = :active, last_run_at = :last_run_at WHERE id = :id');
        $stmt->execute([
            'id' => $settings->getId(),
            'schedule_type' => $settings->getScheduleType(),
            'day_of_month' => $settings->getDayOfMonth(),
            'day_of_week' => $settings->getDayOfWeek(),
            'time_of_day' => $settings->getTimeOfDay(),
            'timezone' => $settings->getTimezone(),
            'looker_url' => $settings->getLookerUrl(),
            'active' => $settings->isActive() ? 1 : 0,
            'last_run_at' => $settings->getLastRunAt()?->format('Y-m-d H:i:s'),
        ]);

        return $settings;
    }

    public function updateLastRunAt(int $id, \DateTimeInterface $lastRunAt): void
    {
        $stmt = $this->pdo->prepare('UPDATE report_settings SET last_run_at = :last_run_at WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'last_run_at' => $lastRunAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): ReportSettings
    {
        $lastRunAt = null;
        if (!empty($row['last_run_at'])) {
            $lastRunAt = new \DateTimeImmutable($row['last_run_at'], new \DateTimeZone('UTC'));
        }

        $createdAt = null;
        if (!empty($row['created_at'])) {
            $createdAt = new \DateTimeImmutable($row['created_at'], new \DateTimeZone('UTC'));
        }

        $updatedAt = null;
        if (!empty($row['updated_at'])) {
            $updatedAt = new \DateTimeImmutable($row['updated_at'], new \DateTimeZone('UTC'));
        }

        return new ReportSettings(
            (int)$row['id'],
            (string)$row['schedule_type'],
            $row['day_of_month'] !== null ? (int)$row['day_of_month'] : null,
            $row['day_of_week'] !== null ? (int)$row['day_of_week'] : null,
            (string)$row['time_of_day'],
            (string)$row['timezone'],
            $row['looker_url'] ?? null,
            (bool)$row['active'],
            $lastRunAt,
            $createdAt,
            $updatedAt
        );
    }
}
