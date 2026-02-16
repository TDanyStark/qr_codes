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

    public function dailyCounts(int $qrCodeId, int $days = 365, ?string $city = null): array
    {
        if ($city === null) {
            $sql = 'SELECT DATE(scanned_at) as day, COUNT(*) as cnt FROM scans WHERE qrcode_id = :id AND scanned_at >= DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY) GROUP BY DATE(scanned_at) ORDER BY day ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT DATE(scanned_at) as day, COUNT(*) as cnt FROM scans WHERE qrcode_id = :id AND city = :city AND scanned_at >= DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY) GROUP BY DATE(scanned_at) ORDER BY day ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':city', $city, PDO::PARAM_STR);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = ['day' => $row['day'], 'cnt' => (int)$row['cnt']];
        }
        return $out;
    }

    public function countryBreakdown(int $qrCodeId, int $limit = 10, ?string $city = null): array
    {
        if ($city === null) {
            $sql = 'SELECT country, COUNT(*) as cnt FROM scans WHERE qrcode_id = :id GROUP BY country ORDER BY cnt DESC LIMIT :limit';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT country, COUNT(*) as cnt FROM scans WHERE qrcode_id = :id AND city = :city GROUP BY country ORDER BY cnt DESC LIMIT :limit';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':city', $city, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = ['country' => $row['country'] ?? null, 'cnt' => (int)$row['cnt']];
        }
        return $out;
    }

    public function cityBreakdown(int $qrCodeId, int $limit = 10, ?string $country = null): array
    {
        if ($country === null) {
            $sql = 'SELECT city, COUNT(*) as cnt FROM scans WHERE qrcode_id = :id GROUP BY city ORDER BY cnt DESC LIMIT :limit';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT city, COUNT(*) as cnt FROM scans WHERE qrcode_id = :id AND country = :country GROUP BY city ORDER BY cnt DESC LIMIT :limit';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':country', $country, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = ['city' => $row['city'] ?? null, 'cnt' => (int)$row['cnt']];
        }
        return $out;
    }

    public function totalCount(int $qrCodeId, ?string $city = null): int
    {
        if ($city === null) {
            $sql = 'SELECT COUNT(*) as total FROM scans WHERE qrcode_id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
        } else {
            $sql = 'SELECT COUNT(*) as total FROM scans WHERE qrcode_id = :id AND city = :city';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $qrCodeId, PDO::PARAM_INT);
            $stmt->bindValue(':city', $city, PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0];
        return (int)$row['total'];
    }
}
