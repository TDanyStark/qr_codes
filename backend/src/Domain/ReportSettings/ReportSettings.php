<?php

declare(strict_types=1);

namespace App\Domain\ReportSettings;

use JsonSerializable;

class ReportSettings implements JsonSerializable
{
    private ?int $id;

    private string $scheduleType;

    private ?int $dayOfMonth;

    private ?int $dayOfWeek;

    private string $timeOfDay;

    private string $timezone;

    private ?string $lookerUrl;

    private bool $active;

    private ?\DateTimeImmutable $lastRunAt;

    private ?\DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        string $scheduleType,
        ?int $dayOfMonth,
        ?int $dayOfWeek,
        string $timeOfDay,
        string $timezone,
        ?string $lookerUrl,
        bool $active = true,
        ?\DateTimeImmutable $lastRunAt = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->scheduleType = $scheduleType;
        $this->dayOfMonth = $dayOfMonth;
        $this->dayOfWeek = $dayOfWeek;
        $this->timeOfDay = $timeOfDay;
        $this->timezone = $timezone;
        $this->lookerUrl = $lookerUrl;
        $this->active = $active;
        $this->lastRunAt = $lastRunAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScheduleType(): string
    {
        return $this->scheduleType;
    }

    public function getDayOfMonth(): ?int
    {
        return $this->dayOfMonth;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function getTimeOfDay(): string
    {
        return $this->timeOfDay;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getLookerUrl(): ?string
    {
        return $this->lookerUrl;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getLastRunAt(): ?\DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'schedule_type' => $this->scheduleType,
            'day_of_month' => $this->dayOfMonth,
            'day_of_week' => $this->dayOfWeek,
            'time_of_day' => $this->timeOfDay,
            'timezone' => $this->timezone,
            'looker_url' => $this->lookerUrl,
            'active' => $this->active,
            'last_run_at' => $this->lastRunAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
