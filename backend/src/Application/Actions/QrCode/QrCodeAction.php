<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Actions\Action;
use App\Domain\QrCode\QrCodeRepository;
use Psr\Log\LoggerInterface;

abstract class QrCodeAction extends Action
{
    protected QrCodeRepository $qrCodeRepository;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository)
    {
        parent::__construct($logger);
        $this->qrCodeRepository = $qrCodeRepository;
    }
}
