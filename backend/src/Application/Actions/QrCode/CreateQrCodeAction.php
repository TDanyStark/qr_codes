<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Actions\Action;
use App\Domain\QrCode\QrCode as DomainQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Psr\Http\Message\ResponseInterface as Response;

class CreateQrCodeAction extends QrCodeAction
{
    // NOTE: This action now generates PNG only (SVG generation removed intentionally).
    protected function action(): Response
    {
        $data = $this->getFormData();

        $targetUrl = $data['target_url'] ?? null;
        $name = $data['name'] ?? null;
        $foreground = $data['foreground'] ?? '#000000';
        $background = $data['background'] ?? '#ffffff';
    // Force PNG-only output. Ignore any requested format.

        if (empty($targetUrl)) {
            return $this->respondWithData(['error' => 'target_url is required'], 400);
        }

        // get user id from jwt
        $jwt = $this->request->getAttribute('jwt');
        $userId = null;
        if (is_array($jwt) && isset($jwt['sub'])) {
            $userId = (int)$jwt['sub'];
        } elseif (is_object($jwt) && isset($jwt->sub)) {
            $userId = (int)$jwt->sub;
        }

        if ($userId === null) {
            return $this->respondWithData(['error' => 'unauthenticated'], 401);
        }

        // generate a token
        $token = bin2hex(random_bytes(16));

        // create domain entity and persist
        $domainQr = new DomainQrCode(null, $token, $userId, $targetUrl, $name);
        $created = $this->qrCodeRepository->create($domainQr);

        // prepare folders
        // Ensure we write into the backend/public folder. Walk up from current dir to find a folder named 'backend'.
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
            // fallback to previous behavior (5 levels up)
            $publicDir = dirname(__DIR__, 5) . '/public';
        } else {
            $publicDir = $backendRoot . '/public';
        }

        $tmpDir = $publicDir . '/tmp/qrcodes';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $pngPath = $tmpDir . '/' . $token . '.png';

        // parse colors from hex to Color objects
        $fgColor = $this->parseHexColor($foreground);
        $bgColor = $this->parseHexColor($background);

        // build PNG using QrCode + PngWriter
        $qrForPng = new EndroidQrCode(
            data: $targetUrl,
            size: 300,
            margin: 10,
            foregroundColor: $fgColor,
            backgroundColor: $bgColor
        );

        $pngWriter = new PngWriter();
        $resultPng = $pngWriter->write($qrForPng);
        file_put_contents($pngPath, $resultPng->getString());

        // Return only the relative path (frontend will use this '/tmp/...' path)
        $links = [
            'png' => '/tmp/qrcodes/' . $token . '.png',
        ];

        return $this->respondWithData([
            'qr' => $created,
            'links' => $links,
        ], 201);
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

}
