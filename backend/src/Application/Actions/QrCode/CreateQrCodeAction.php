<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Actions\Action;
use App\Domain\QrCode\QrCode as DomainQrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Psr\Http\Message\ResponseInterface as Response;

class CreateQrCodeAction extends QrCodeAction
{
    protected function action(): Response
    {
        $data = $this->getFormData();

        $targetUrl = $data['target_url'] ?? null;
        $name = $data['name'] ?? null;
        $foreground = $data['foreground'] ?? '#000000';
        $background = $data['background'] ?? '#ffffff';
        $format = strtolower($data['format'] ?? 'png'); // png or svg

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
        $publicDir = dirname(__DIR__, 5) . '/public';
        $tmpDir = $publicDir . '/tmp/qrcodes';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $svgPath = $tmpDir . '/' . $token . '.svg';
        $pngPath = $tmpDir . '/' . $token . '.png';

        // parse colors from hex to Color objects
        $fgColor = $this->parseHexColor($foreground);
        $bgColor = $this->parseHexColor($background);

        // build SVG using QrCode + SvgWriter (Endroid QR Code v6 API)
        $qrForSvg = new EndroidQrCode(
            data: $targetUrl,
            size: 300,
            margin: 10,
            foregroundColor: $fgColor,
            backgroundColor: $bgColor
        );

        $svgWriter = new SvgWriter();
        $resultSvg = $svgWriter->write($qrForSvg);
        file_put_contents($svgPath, $resultSvg->getString());

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

        $baseUrl = $this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getHost();
        $port = $this->request->getUri()->getPort();
        if ($port) {
            $baseUrl .= ':' . $port;
        }

        $links = [
            'svg' => $baseUrl . '/tmp/qrcodes/' . $token . '.svg',
            'png' => $baseUrl . '/tmp/qrcodes/' . $token . '.png',
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
