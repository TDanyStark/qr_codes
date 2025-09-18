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
use App\Infrastructure\Security\JwtServiceInterface;


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
        JwtServiceInterface::class => function (ContainerInterface $c) {
            return new JwtService($c->get(SettingsInterface::class));
        },
        MailerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $logger = $c->get(LoggerInterface::class);
            $driver = $settings->get('mail.driver') ?? getenv('MAIL_DRIVER') ?: getenv('MAILER_DRIVER') ?: 'log';

            if (strtolower($driver) === 'smtp') {
                return new SmtpMailer($logger, $settings);
            }

            return new BasicMailer($logger, $settings);
        }
        ,
        \App\Application\Middleware\AdminRoleMiddleware::class => function (ContainerInterface $c) {
            return new \App\Application\Middleware\AdminRoleMiddleware();
        }
    ]);
};
