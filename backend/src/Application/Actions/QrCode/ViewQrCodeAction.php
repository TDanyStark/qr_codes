<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use App\Domain\QrCode\QrCodeRepository;
use App\Application\Settings\SettingsInterface;
use App\Application\Services\QrCode\QrWriterInterface;
use App\Application\Services\QrCode\FileStorageInterface;
use App\Application\Services\QrCode\QrColorParserInterface;
use Endroid\QrCode\Color\Color;
use App\Infrastructure\Utils\PublicDirectoryResolver;

class ViewQrCodeAction extends QrCodeAction
{
    private QrWriterInterface $qrWriter;
    private FileStorageInterface $fileStorage;
    private QrColorParserInterface $colorParser;
    private PublicDirectoryResolver $publicResolver;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, SettingsInterface $settings, QrWriterInterface $qrWriter, FileStorageInterface $fileStorage, QrColorParserInterface $colorParser, PublicDirectoryResolver $publicResolver)
    {
        parent::__construct($logger, $qrCodeRepository, $settings);
        $this->qrWriter = $qrWriter;
        $this->fileStorage = $fileStorage;
        $this->colorParser = $colorParser;
        $this->publicResolver = $publicResolver;
    }

    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');

        try {
            $qr = $this->qrCodeRepository->findOfId($id);
        } catch (\Throwable $e) {
            return $this->respondWithData(['error' => 'QR not found'], 404);
        }

        $token = $qr->getToken();

        // build redirect URL
        $baseUrl = getenv('URL_BASE') ?: '';
        $baseUrl = rtrim($baseUrl, '/');
        $redirect = ($baseUrl !== '' ? $baseUrl : '') . '/r/' . $token;

        $pngRel = '/tmp/qrcodes/' . $token . '.png';
        $svgRel = '/tmp/qrcodes/' . $token . '.svg';

        $publicDir = $this->publicResolver->getPublicDir();

        $pngFull = $publicDir . $pngRel;
        $svgFull = $publicDir . $svgRel;

        $pngExists = is_file($pngFull);
        $svgExists = is_file($svgFull);

        // If files are missing regenerate them using writer and storage
        if (!($pngExists && $svgExists)) {
            // use default colors (black on white) — no color info is stored currently
            $fg = $this->colorParser->parseHexColor('#000000');
            $bg = $this->colorParser->parseHexColor('#ffffff');

            try {
                $generated = $this->qrWriter->generate($redirect, $fg, $bg);
                // save
                $this->fileStorage->save($pngRel, $generated['png']);
                $this->fileStorage->save($svgRel, $generated['svg']);
                $pngExists = true;
                $svgExists = true;
            } catch (\Throwable $t) {
                $this->logger->error('Failed to (re)generate QR images: ' . $t->getMessage());
            }
        }

        $links = [
            'png' => $pngRel,
            'svg' => $svgRel,
            'redirect' => $redirect,
        ];

        $data = [
            'qr' => $qr->toArray(),
            'links' => $links,
            'images_present' => ($pngExists && $svgExists),
        ];

        return $this->respondWithData($data, 200);
    }

    // parseHexColor removed — use QrColorParserInterface
}
