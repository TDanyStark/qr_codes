<?php

declare(strict_types=1);

namespace App\Application\Services\QrCode;

use App\Domain\QrCode\QrCode as DomainQrCode;
use App\Domain\QrCode\QrCodeRepository;
use Endroid\QrCode\Color\Color;
use App\Application\Services\UrlBuilder;

class QrCodeCreator
{
    private QrCodeRepository $qrCodeRepository;
    private QrWriterInterface $qrWriter;
    private FileStorageInterface $fileStorage;
    private QrColorParserInterface $colorParser;
    private UrlBuilder $urlBuilder;

    public function __construct(QrCodeRepository $qrCodeRepository, QrWriterInterface $qrWriter, FileStorageInterface $fileStorage, QrColorParserInterface $colorParser, UrlBuilder $urlBuilder)
    {
        $this->qrCodeRepository = $qrCodeRepository;
        $this->qrWriter = $qrWriter;
        $this->fileStorage = $fileStorage;
        $this->colorParser = $colorParser;
        $this->urlBuilder = $urlBuilder;
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

        $fgColor = $this->colorParser->parseHexColor($foreground);
        $bgColor = $this->colorParser->parseHexColor($background);

        // build redirect URL
        $qrData = $this->urlBuilder->buildRedirectUrl($token);

        // generate via injected writer
        $generated = $this->qrWriter->generate($qrData, $fgColor, $bgColor);

        // store via injected storage
        $pngRel = 'tmp/qrcodes/' . $token . '.png';
        $svgRel = 'tmp/qrcodes/' . $token . '.svg';
        $this->fileStorage->save($pngRel, $generated['png']);
        $this->fileStorage->save($svgRel, $generated['svg']);

        $links = [
            'png' => '/' . $pngRel,
            'svg' => '/' . $svgRel,
            'redirect' => $qrData,
        ];

        return ['qr' => $created, 'links' => $links];
    }

    // parseHexColor removed — use QrColorParserInterface
}
