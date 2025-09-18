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
    $stmt = $this->pdo->prepare('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id WHERE owner_user_id = :owner ORDER BY q.id');
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
                $createdAt,
                $row['owner_name'] ?? null,
                $row['owner_email'] ?? null
            );
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
    $stmt = $this->pdo->query('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id ORDER BY q.id');
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
                $createdAt,
                $row['owner_name'] ?? null,
                $row['owner_email'] ?? null
            );
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function findOfId(int $id): QrCode
    {
    $stmt = $this->pdo->prepare('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id WHERE q.id = :id');
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
            $createdAt,
            $row['owner_name'] ?? null,
            $row['owner_email'] ?? null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByToken(string $token): QrCode
    {
    $stmt = $this->pdo->prepare('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id WHERE q.token = :token');
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
            $createdAt,
            $row['owner_name'] ?? null,
            $row['owner_email'] ?? null
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

        // try to fetch owner info for the created record
        $ownerName = null;
        $ownerEmail = null;
        $stmt2 = $this->pdo->prepare('SELECT name, email FROM users WHERE id = :id');
        $stmt2->execute(['id' => $qrCode->getOwnerUserId()]);
        $userRow = $stmt2->fetch();
        if ($userRow) {
            $ownerName = $userRow['name'] ?? null;
            $ownerEmail = $userRow['email'] ?? null;
        }

        return new QrCode(
            $id,
            $qrCode->getToken(),
            $qrCode->getOwnerUserId(),
            $qrCode->getTargetUrl(),
            $qrCode->getName(),
            new \DateTimeImmutable(),
            $ownerName,
            $ownerEmail
        );
    }
}
