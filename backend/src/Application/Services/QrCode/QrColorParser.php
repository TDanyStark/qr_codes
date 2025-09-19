<?php

declare(strict_types=1);

namespace App\Application\Services\QrCode;

use Endroid\QrCode\Color\Color;

class QrColorParser implements QrColorParserInterface
{
    public function parseHexColor(string $hex): Color
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat($hex[0], 2));
            $g = hexdec(str_repeat($hex[1], 2));
            $b = hexdec(str_repeat($hex[2], 2));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return new Color($r, $g, $b);
    }
}
