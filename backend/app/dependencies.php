<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use App\Infrastructure\Security\JwtService;
use \App\Infrastructure\Mailer\MailerInterface;
use \App\Infrastructure\Mailer\BasicMailer;
use \App\Infrastructure\Mailer\SmtpMailer;
use App\Application\Security\JwtServiceInterface;
use \App\Application\Middleware\AdminRoleMiddleware;
use \App\Application\Services\QrCode\QrCodeCreator;
use App\Application\Services\QrCode\QrWriterInterface;
use App\Application\Services\QrCode\FileStorageInterface;
use App\Application\Services\QrCode\QrColorParserInterface;
use App\Application\Services\QrCode\QrColorParser;
use App\Infrastructure\Qr\EndroidQrWriter;
use App\Infrastructure\Persistence\LocalFileStorage;
use App\Application\Services\GeoIp\GeoIpServiceInterface;
use App\Infrastructure\GeoIp\IpDataGeoIpService;


return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        PDO::class => function (ContainerInterface $c) {
            // Read DB settings from environment variables (fallback to common names)
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_DATABASE') ?: 'qr_codes';
            $user = getenv('DB_USERNAME') ?: '';
            $pass = getenv('DB_PASSWORD') ?: '';

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            return new PDO($dsn, $user, $pass, $options);
        },
        MailerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $logger = $c->get(LoggerInterface::class);
            $driver = $settings->get('mail.driver') ?? getenv('MAIL_DRIVER') ?: getenv('MAILER_DRIVER') ?: 'log';

            if (strtolower($driver) === 'smtp') {
                return new SmtpMailer($logger, $settings);
            }

            return new BasicMailer($logger, $settings);
        },
        JwtServiceInterface::class => \DI\autowire(JwtService::class),
        AdminRoleMiddleware::class => \DI\autowire(AdminRoleMiddleware::class),
        QrCodeCreator::class => \DI\autowire(QrCodeCreator::class),
        QrWriterInterface::class => \DI\autowire(EndroidQrWriter::class),
        FileStorageInterface::class => \DI\autowire(LocalFileStorage::class),
        QrColorParserInterface::class => \DI\autowire(QrColorParser::class),
        GeoIpServiceInterface::class => \DI\autowire(IpDataGeoIpService::class),
    ]);
};
