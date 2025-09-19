<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Domain\QrCode\QrCodeNotFoundException;
use App\Domain\Scan\Scan;
use App\Domain\Scan\ScanRepository;
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
        private ScanRepository $scanRepository
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
        // City/Country could be resolved via external geo service. For ahora se dejan null.
        $scan = new Scan(null, $qr->getId() ?? 0, null, $ip, $userAgent, null, null);
        // We swallow exceptions intentionally to not break redirect
        try { $this->scanRepository->create($scan); } catch (\Throwable $t) { $this->logger->error('Failed to record scan: ' . $t->getMessage()); }

        return $this->response
            ->withHeader('Location', $qr->getTargetUrl())
            ->withStatus(302);
    }

    private function getClientIp(): ?string
    {
        $serverParams = $this->request->getServerParams();
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];
        foreach ($ipHeaders as $h) {
            if (!empty($serverParams[$h])) {
                $value = $serverParams[$h];
                if ($h === 'HTTP_X_FORWARDED_FOR') {
                    $parts = explode(',', $value);
                    $value = trim($parts[0]);
                }
                return $value;
            }
        }
        return null;
    }
}
