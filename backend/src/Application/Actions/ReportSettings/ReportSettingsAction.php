<?php

declare(strict_types=1);

namespace App\Application\Actions\ReportSettings;

use App\Application\Actions\Action;
use App\Domain\ReportSettings\ReportSettings;
use App\Domain\ReportSettings\ReportSettingsRepository;
use Psr\Log\LoggerInterface;

abstract class ReportSettingsAction extends Action
{
    protected ReportSettingsRepository $settingsRepository;

    public function __construct(LoggerInterface $logger, ReportSettingsRepository $settingsRepository)
    {
        parent::__construct($logger);
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeInput(): array
    {
        $input = $this->getFormData();
        if (is_array($input)) {
            return $input;
        }
        if (is_object($input)) {
            return (array)$input;
        }
        return [];
    }

    protected function buildSettings(array $data, ?ReportSettings $existing = null): ReportSettings
    {
        $scheduleType = $data['schedule_type'] ?? ($existing?->getScheduleType() ?? 'monthly');
        if (!in_array($scheduleType, ['monthly', 'weekly'], true)) {
            throw new \InvalidArgumentException('schedule_type must be monthly or weekly');
        }

        $timeOfDay = $data['time_of_day'] ?? ($existing?->getTimeOfDay() ?? '08:00:00');
        $timezone = $data['timezone'] ?? ($existing?->getTimezone() ?? 'UTC');
        $lookerUrl = array_key_exists('looker_url', $data)
            ? (empty($data['looker_url']) ? null : (string)$data['looker_url'])
            : ($existing?->getLookerUrl());
        $active = array_key_exists('active', $data)
            ? (bool)$data['active']
            : ($existing?->isActive() ?? false);

        $dayOfMonth = $data['day_of_month'] ?? ($existing?->getDayOfMonth());
        $dayOfWeek = $data['day_of_week'] ?? ($existing?->getDayOfWeek());

        if ($scheduleType === 'monthly') {
            $dayOfMonth = $dayOfMonth !== null ? (int)$dayOfMonth : null;
            $dayOfWeek = null;
            if ($dayOfMonth === null || $dayOfMonth < 1 || $dayOfMonth > 31) {
                throw new \InvalidArgumentException('day_of_month is required for monthly schedule');
            }
        } else {
            $dayOfWeek = $dayOfWeek !== null ? (int)$dayOfWeek : null;
            $dayOfMonth = null;
            if ($dayOfWeek === null || $dayOfWeek < 1 || $dayOfWeek > 7) {
                throw new \InvalidArgumentException('day_of_week is required for weekly schedule');
            }
        }

        return new ReportSettings(
            $existing?->getId(),
            $scheduleType,
            $dayOfMonth,
            $dayOfWeek,
            (string)$timeOfDay,
            (string)$timezone,
            $lookerUrl,
            $active,
            $existing?->getLastRunAt(),
            $existing?->getCreatedAt(),
            $existing?->getUpdatedAt()
        );
    }
}
