<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Scan;

use App\Domain\Scan\Scan;
use App\Domain\Scan\ScanRepository;
use PDO;

class PdoScanRepository implements ScanRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(Scan $scan): Scan
    {
        $stmt = $this->pdo->prepare('INSERT INTO scans (qrcode_id, scanned_at, ip, user_agent, city, country) VALUES (:qrcode_id, :scanned_at, :ip, :user_agent, :city, :country)');
        $stmt->execute([
            'qrcode_id' => $scan->getQrCodeId(),
            'scanned_at' => $scan->getScannedAt()->format('Y-m-d H:i:s'),
            'ip' => $scan->getIp(),
            'user_agent' => $scan->getUserAgent(),
            'city' => $scan->getCity(),
            'country' => $scan->getCountry(),
        ]);
        $id = (int)$this->pdo->lastInsertId();
        return new Scan(
            $id,
            $scan->getQrCodeId(),
            $scan->getScannedAt(),
            $scan->getIp(),
            $scan->getUserAgent(),
            $scan->getCity(),
            $scan->getCountry()
        );
    }

    public function findByQrCode(int $qrCodeId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare('SELECT id, qrcode_id, scanned_at, ip, user_agent, city, country FROM scans WHERE qrcode_id = :id ORDER BY id DESC LIMIT :limit');
        $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $items = [];
        foreach ($rows as $row) {
            $items[] = new Scan(
                (int)$row['id'],
                (int)$row['qrcode_id'],
                new \DateTimeImmutable($row['scanned_at']),
                $row['ip'] ?? null,
                $row['user_agent'] ?? null,
                $row['city'] ?? null,
                $row['country'] ?? null
            );
        }
        return $items;
    }
}
