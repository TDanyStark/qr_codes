<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use App\Domain\QrCode\QrCodeRepository;
use App\Domain\Scan\ScanRepository;
use App\Application\Actions\Action;
use App\Infrastructure\Utils\PublicDirectoryResolver;

class StatsQrCodeCsvAction extends Action
{
  private QrCodeRepository $qrCodeRepository;
  private ScanRepository $scanRepository;
  private PublicDirectoryResolver $publicResolver;

  public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, ScanRepository $scanRepository, PublicDirectoryResolver $publicResolver)
  {
    parent::__construct($logger);
    $this->qrCodeRepository = $qrCodeRepository;
    $this->scanRepository = $scanRepository;
    $this->publicResolver = $publicResolver;
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

    $queryParams = $this->request->getQueryParams();
    $city = isset($queryParams['city']) && $queryParams['city'] !== '' ? (string)$queryParams['city'] : null;

    // fetch raw scans (we'll use findByQrCode to get details)
    $scans = $this->scanRepository->findByQrCode($id, 10000); // large limit; consider streaming in future

    // Build CSV content
    $headers = ['id', 'qrcode_id', 'scanned_at', 'ip', 'user_agent', 'city', 'country'];

    $lines = [];
    $lines[] = implode(',', $headers);
    foreach ($scans as $s) {
      // each $s is a domain Scan object
      if (is_object($s) && method_exists($s, 'toArray')) {
        $row = $s->toArray();
        // escape double quotes and wrap fields if needed
        $escaped = array_map(function ($v) {
          if ($v === null) return '';
          $str = (string)$v;
          $str = str_replace('"', '""', $str);
          if (preg_match('/[",\n\r,]/', $str)) {
            return '"' . $str . '"';
          }
          return $str;
        }, [$row['id'], $row['qrcode_id'], $row['scanned_at'], $row['ip'], $row['user_agent'], $row['city'], $row['country']]);
        $lines[] = implode(',', $escaped);
      }
    }

    $csv = implode("\r\n", $lines);

    // Ensure tmp directory exists (public/tmp/reports)
    $publicDir = $this->publicResolver->getPublicDir();
    $tmpDir = $publicDir . '/tmp/reports';
    if (!is_dir($tmpDir)) {
      @mkdir($tmpDir, 0755, true);
    }

    $filename = sprintf('qrcode_%d_scans_%s.csv', $id, bin2hex(random_bytes(6)));
    $filePath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

    // write CSV to file
    file_put_contents($filePath, $csv, LOCK_EX);

    $downloadUrl = '/tmp/reports/' . $filename;

    return $this->respondWithData(['downloadUrl' => $downloadUrl], 200);
  }
}
