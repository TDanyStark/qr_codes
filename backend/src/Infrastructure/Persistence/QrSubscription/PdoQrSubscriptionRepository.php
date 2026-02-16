<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QrSubscription;

use App\Domain\QrSubscription\QrSubscription;
use App\Domain\QrSubscription\QrSubscriptionRepository;
use PDO;
use Psr\Log\LoggerInterface;

class PdoQrSubscriptionRepository implements QrSubscriptionRepository
{
    private PDO $pdo;

    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function create(int $qrCodeId, int $userId): QrSubscription
    {
        $stmt = $this->pdo->prepare('INSERT INTO qr_subscriptions (qrcode_id, user_id) VALUES (:qrcode_id, :user_id)');
        $stmt->execute([
            'qrcode_id' => $qrCodeId,
            'user_id' => $userId,
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return new QrSubscription($id, $qrCodeId, $userId, new \DateTimeImmutable());
    }

    public function delete(int $qrCodeId, int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM qr_subscriptions WHERE qrcode_id = :qrcode_id AND user_id = :user_id');
        $stmt->execute([
            'qrcode_id' => $qrCodeId,
            'user_id' => $userId,
        ]);
    }

    public function listByQrCode(int $qrCodeId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, qrcode_id, user_id, created_at FROM qr_subscriptions WHERE qrcode_id = :qrcode_id ORDER BY id');
        $stmt->execute(['qrcode_id' => $qrCodeId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->mapRows($rows);
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, qrcode_id, user_id, created_at FROM qr_subscriptions WHERE user_id = :user_id ORDER BY id');
        $stmt->execute(['user_id' => $userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $this->mapRows($rows);
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, qrcode_id, user_id, created_at FROM qr_subscriptions ORDER BY id');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $this->mapRows($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return QrSubscription[]
     */
    private function mapRows(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $createdAt = null;
            if (!empty($row['created_at'])) {
                $createdAt = new \DateTimeImmutable($row['created_at']);
            }
            $items[] = new QrSubscription(
                (int)$row['id'],
                (int)$row['qrcode_id'],
                (int)$row['user_id'],
                $createdAt
            );
        }
        return $items;
    }
}
