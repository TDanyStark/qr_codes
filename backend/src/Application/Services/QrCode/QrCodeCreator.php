<?php

declare(strict_types=1);

namespace App\Application\Services\QrCode;

use App\Domain\QrCode\QrCode as DomainQrCode;
use App\Domain\QrCode\QrCodeRepository;
use App\Application\Settings\SettingsInterface;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Psr\Log\LoggerInterface;

class QrCodeCreator
{
    private QrCodeRepository $qrCodeRepository;
    private SettingsInterface $settings;
    private LoggerInterface $logger;

    public function __construct(QrCodeRepository $qrCodeRepository, SettingsInterface $settings, LoggerInterface $logger)
    {
        $this->qrCodeRepository = $qrCodeRepository;
        $this->settings = $settings;
        $this->logger = $logger;
    }

    /**
     * Create a QR code record, generate PNG and SVG files and return created entity + links
     *
     * @param array $data expects keys: target_url (required), name, foreground, background
     * @param int $userId owner user id
     * @return array ['qr' => DomainQrCode, 'links' => array]
     */
    public function createFromData(array $data, int $userId): array
    {
        $targetUrl = $data['target_url'] ?? null;
        if (empty($targetUrl)) {
            throw new \InvalidArgumentException('target_url is required');
        }

        $name = $data['name'] ?? null;
        $foreground = $data['foreground'] ?? '#000000';
        $background = $data['background'] ?? '#ffffff';

        $token = bin2hex(random_bytes(16));

        $domainQr = new DomainQrCode(null, $token, $userId, $targetUrl, $name);
        $created = $this->qrCodeRepository->create($domainQr);

        // prepare public tmp dir
        $publicDir = $this->resolvePublicDir();
        $tmpDir = $publicDir . '/tmp/qrcodes';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $pngPath = $tmpDir . '/' . $token . '.png';
        $svgPath = $tmpDir . '/' . $token . '.svg';

        $fgColor = $this->parseHexColor($foreground);
        $bgColor = $this->parseHexColor($background);

        // build redirect URL
        $baseUrl = getenv('URL_BASE') ?: '';
        $baseUrl = rtrim($baseUrl, '/');
        $qrData = ($baseUrl !== '' ? $baseUrl : '') . '/r/' . $token;

        // create PNG
        $qrForPng = new EndroidQrCode(
            data: $qrData,
            size: 1000,
            margin: 10,
            foregroundColor: $fgColor,
            backgroundColor: $bgColor
        );
        $pngWriter = new PngWriter();
        $resultPng = $pngWriter->write($qrForPng);
        file_put_contents($pngPath, $resultPng->getString());

        // create SVG
        $svgWriter = new SvgWriter();
        $qrForSvg = new EndroidQrCode(
            data: $qrData,
            size: 1000,
            margin: 10,
            foregroundColor: $fgColor,
            backgroundColor: $bgColor
        );
        $resultSvg = $svgWriter->write($qrForSvg);
        file_put_contents($svgPath, $resultSvg->getString());

        $links = [
            'png' => '/tmp/qrcodes/' . $token . '.png',
            'svg' => '/tmp/qrcodes/' . $token . '.svg',
            'redirect' => $qrData,
        ];

        return ['qr' => $created, 'links' => $links];
    }

    private function parseHexColor(string $hex): Color
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

    private function resolvePublicDir(): string
    {
        // try to find backend root by walking up from this file
        $dir = __DIR__;
        $backendRoot = null;
        while ($dir && $dir !== dirname($dir)) {
            if (basename($dir) === 'backend') {
                $backendRoot = $dir;
                break;
            }
            $dir = dirname($dir);
        }

        if ($backendRoot === null) {
            return dirname(__DIR__, 5) . '/public';
        }

        return $backendRoot . '/public';
    }
}
