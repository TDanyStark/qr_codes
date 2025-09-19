<?php

declare(strict_types=1);

namespace App\Application\Services\QrCode;

use Endroid\QrCode\Color\Color;

interface QrColorParserInterface
{
    public function parseHexColor(string $hex): Color;
}
