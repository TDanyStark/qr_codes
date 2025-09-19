<?php

declare(strict_types=1);

namespace App\Infrastructure\Qr;

use App\Application\Services\QrCode\QrWriterInterface;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Color\Color;

class EndroidQrWriter implements QrWriterInterface
{
    public function generate(string $data, Color $foreground, Color $background): array
    {
        $pngWriter = new PngWriter();
        $svgWriter = new SvgWriter();

        $qrForPng = new EndroidQrCode(
            data: $data,
            size: 1000,
            margin: 10,
            foregroundColor: $foreground,
            backgroundColor: $background
        );
        $resultPng = $pngWriter->write($qrForPng);

        $qrForSvg = new EndroidQrCode(
            data: $data,
            size: 1000,
            margin: 10,
            foregroundColor: $foreground,
            backgroundColor: $background
        );
        $resultSvg = $svgWriter->write($qrForSvg);

        return [
            'png' => $resultPng->getString(),
            'svg' => $resultSvg->getString(),
        ];
    }
}
