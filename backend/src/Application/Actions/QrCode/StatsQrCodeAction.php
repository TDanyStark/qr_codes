<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use App\Domain\QrCode\QrCodeRepository;
use App\Domain\Scan\ScanRepository;
use App\Application\Actions\Action;

class StatsQrCodeAction extends Action
{
    private QrCodeRepository $qrCodeRepository;
    private ScanRepository $scanRepository;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, ScanRepository $scanRepository)
    {
        parent::__construct($logger);
        $this->qrCodeRepository = $qrCodeRepository;
        $this->scanRepository = $scanRepository;
    }

    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');

        // ensure QR exists
        try {
            $qr = $this->qrCodeRepository->findOfId($id);
        } catch (\Throwable $e) {
            return $this->respondWithData(['error' => 'QR not found'], 404);
        }

    // Use ScanRepository to fetch aggregated data
    $daily = $this->scanRepository->dailyCounts($id, 30);
    $countries = $this->scanRepository->countryBreakdown($id, 10);
    $total = $this->scanRepository->totalCount($id);

        $data = [
            'qr' => $qr->toArray(),
            'daily' => $daily,
            'countries' => $countries,
            'total' => $total,
        ];

        return $this->respondWithData($data, 200);
    }
}
