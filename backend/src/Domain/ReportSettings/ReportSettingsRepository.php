<?php

declare(strict_types=1);

namespace App\Domain\ReportSettings;

interface ReportSettingsRepository
{
    public function getActive(): ?ReportSettings;

    public function findById(int $id): ?ReportSettings;

    public function save(ReportSettings $settings): ReportSettings;

    public function updateLastRunAt(int $id, \DateTimeInterface $lastRunAt): void;
}
