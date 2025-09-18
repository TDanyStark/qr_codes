<?php

declare(strict_types=1);

use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\PdoUserRepository;
use App\Domain\QrCode\QrCodeRepository;
use App\Infrastructure\Persistence\QrCode\PdoQrCodeRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(PdoUserRepository::class),
        QrCodeRepository::class => \DI\autowire(PdoQrCodeRepository::class),
    ]);
};
