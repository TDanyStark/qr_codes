<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Domain\QrCode\QrCodeNotFoundException;
use App\Domain\Scan\Scan;
use App\Domain\Scan\ScanRepository;
use App\Application\Services\GeoIp\GeoIpServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use \Psr\Log\LoggerInterface;
use \App\Domain\QrCode\QrCodeRepository;
use \App\Application\Settings\SettingsInterface;

class RedirectQrCodeAction extends QrCodeAction
{
    public function __construct(
        LoggerInterface $logger,
        QrCodeRepository $qrCodeRepository,
        SettingsInterface $settings,
        private ScanRepository $scanRepository,
        private GeoIpServiceInterface $geoIpService
    ) {
        parent::__construct($logger, $qrCodeRepository, $settings);
    }

    protected function action(): Response
    {
        $code = $this->resolveArg('code');
        try {
            $qr = $this->qrCodeRepository->findByToken($code);
        } catch (QrCodeNotFoundException $e) {
            $payload = json_encode(['error' => 'QR Code not found']);
            $this->response->getBody()->write($payload);
            return $this->response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Gather scan data
        $ip = $this->getClientIp();
        $userAgent = $this->request->getHeaderLine('User-Agent') ?: null;
        $city = null;
        $country = null;
        if ($ip) {
            $geo = $this->geoIpService->lookup($ip);
            if (!empty($geo)) {
                $city = $geo['city'] ?? null;
                $country = $geo['country'] ?? null;
            }
        }
        $scan = new Scan(null, $qr->getId() ?? 0, null, $ip, $userAgent, $city, $country);
        // We swallow exceptions intentionally to not break redirect
        try { $this->scanRepository->create($scan); } catch (\Throwable $t) { $this->logger->error('Failed to record scan: ' . $t->getMessage()); }

        return $this->response
            ->withHeader('Location', $qr->getTargetUrl())
            ->withStatus(302);
    }

    private function getClientIp(): ?string
    {
        $serverParams = $this->request->getServerParams();
        // Prioritized headers for real client IP behind proxies/CDN
        $candidates = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        foreach ($candidates as $key) {
            if (!empty($serverParams[$key])) {
                $raw = $serverParams[$key];
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $raw = explode(',', $raw)[0] ?? $raw;
                }
                $raw = trim($raw);
                if (filter_var($raw, FILTER_VALIDATE_IP)) {
                    return $raw;
                }
            }
        }
        return null;
    }
}
