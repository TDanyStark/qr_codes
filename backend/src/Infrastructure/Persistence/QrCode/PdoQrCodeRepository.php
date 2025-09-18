<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\QrCode;

use App\Domain\QrCode\QrCode;
use App\Domain\QrCode\QrCodeNotFoundException;
use App\Domain\QrCode\QrCodeRepository;
use PDO;
use Psr\Log\LoggerInterface;

class PdoQrCodeRepository implements QrCodeRepository
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllForUser(int $ownerUserId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, token, owner_user_id, target_url, name, created_at FROM qrcodes WHERE owner_user_id = :owner ORDER BY id');
        $stmt->execute(['owner' => $ownerUserId]);
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $createdAt = null;
            if (!empty($row['created_at'])) {
                $createdAt = new \DateTimeImmutable($row['created_at']);
            }

            $items[] = new QrCode(
                (int)$row['id'],
                $row['token'] ?? '',
                (int)$row['owner_user_id'],
                $row['target_url'] ?? '',
                $row['name'] ?? null,
                $createdAt
            );
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, token, owner_user_id, target_url, name, created_at FROM qrcodes ORDER BY id');
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $createdAt = null;
            if (!empty($row['created_at'])) {
                $createdAt = new \DateTimeImmutable($row['created_at']);
            }

            $items[] = new QrCode(
                (int)$row['id'],
                $row['token'] ?? '',
                (int)$row['owner_user_id'],
                $row['target_url'] ?? '',
                $row['name'] ?? null,
                $createdAt
            );
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findOfId(int $id): QrCode
    {
        $stmt = $this->pdo->prepare('SELECT id, token, owner_user_id, target_url, name, created_at FROM qrcodes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new QrCodeNotFoundException();
        }

        $createdAt = null;
        if (!empty($row['created_at'])) {
            $createdAt = new \DateTimeImmutable($row['created_at']);
        }

        return new QrCode(
            (int)$row['id'],
            $row['token'] ?? '',
            (int)$row['owner_user_id'],
            $row['target_url'] ?? '',
            $row['name'] ?? null,
            $createdAt
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByToken(string $token): QrCode
    {
        $stmt = $this->pdo->prepare('SELECT id, token, owner_user_id, target_url, name, created_at FROM qrcodes WHERE token = :token');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new QrCodeNotFoundException();
        }

        $createdAt = null;
        if (!empty($row['created_at'])) {
            $createdAt = new \DateTimeImmutable($row['created_at']);
        }

        return new QrCode(
            (int)$row['id'],
            $row['token'] ?? '',
            (int)$row['owner_user_id'],
            $row['target_url'] ?? '',
            $row['name'] ?? null,
            $createdAt
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(QrCode $qrCode): QrCode
    {
        $stmt = $this->pdo->prepare('INSERT INTO qrcodes (token, owner_user_id, target_url, name) VALUES (:token, :owner, :target_url, :name)');
        $stmt->execute([
            'token' => $qrCode->getToken(),
            'owner' => $qrCode->getOwnerUserId(),
            'target_url' => $qrCode->getTargetUrl(),
            'name' => $qrCode->getName(),
        ]);

        $id = (int)$this->pdo->lastInsertId();

        return new QrCode(
            $id,
            $qrCode->getToken(),
            $qrCode->getOwnerUserId(),
            $qrCode->getTargetUrl(),
            $qrCode->getName(),
            new \DateTimeImmutable()
        );
    }
}
