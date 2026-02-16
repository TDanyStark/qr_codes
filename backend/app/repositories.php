<?php

declare(strict_types=1);

use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\PdoUserRepository;
use App\Domain\QrCode\QrCodeRepository;
use App\Infrastructure\Persistence\QrCode\PdoQrCodeRepository;
use App\Domain\Scan\ScanRepository;
use App\Infrastructure\Persistence\Scan\PdoScanRepository;
use App\Domain\QrSubscription\QrSubscriptionRepository;
use App\Infrastructure\Persistence\QrSubscription\PdoQrSubscriptionRepository;
use App\Domain\ReportSettings\ReportSettingsRepository;
use App\Infrastructure\Persistence\ReportSettings\PdoReportSettingsRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(PdoUserRepository::class),
        QrCodeRepository::class => \DI\autowire(PdoQrCodeRepository::class),
        ScanRepository::class => \DI\autowire(PdoScanRepository::class),
        QrSubscriptionRepository::class => \DI\autowire(PdoQrSubscriptionRepository::class),
        ReportSettingsRepository::class => \DI\autowire(PdoReportSettingsRepository::class),
    ]);
};
