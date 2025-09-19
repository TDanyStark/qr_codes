<?php

declare(strict_types=1);

namespace App\Application\Services\QrCode;

use Endroid\QrCode\Color\Color;

interface QrWriterInterface
{
    /**
     * Generate PNG and SVG representations for given data and colors.
     * Returns an array with keys 'png' (binary string) and 'svg' (string).
     *
     * @param string $data
     * @param Color $foreground
     * @param Color $background
     * @return array{png:string,svg:string}
     */
    public function generate(string $data, Color $foreground, Color $background): array;
}
