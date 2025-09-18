<?php

declare(strict_types=1);

namespace App\Domain\QrCode;

use App\Domain\DomainException\DomainRecordNotFoundException;

class QrCodeNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The QR code you requested does not exist.';
}
