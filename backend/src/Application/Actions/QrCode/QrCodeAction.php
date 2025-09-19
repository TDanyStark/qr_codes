<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Actions\Action;
use App\Domain\QrCode\QrCodeRepository;
use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;

abstract class QrCodeAction extends Action
{
    protected QrCodeRepository $qrCodeRepository;
    protected SettingsInterface $settings;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, SettingsInterface $settings)
    {
        parent::__construct($logger);
        $this->qrCodeRepository = $qrCodeRepository;
        $this->settings = $settings;
    }
}
