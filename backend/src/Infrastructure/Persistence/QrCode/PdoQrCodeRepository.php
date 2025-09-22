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
     * Paginated list with optional search and owner restriction
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $query
     * @param int|null $ownerUserId
     * @return array{items: QrCode[], total: int}
     */
    public function list(int $page, int $perPage, ?string $query = null, ?int $ownerUserId = null): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $where = [];
        $params = [];

        if ($ownerUserId !== null) {
            $where[] = 'q.owner_user_id = :owner';
            $params['owner'] = $ownerUserId;
        }

        if ($query !== null && $query !== '') {
            // search across token, name, target_url, owner name and owner email
            // use unique placeholders for each occurrence to avoid driver issues with repeated named params
            $where[] = '(q.token LIKE :q_token OR q.name LIKE :q_name OR q.target_url LIKE :q_target OR u.name LIKE :q_uname OR u.email LIKE :q_uemail)';
            $like = '%' . $query . '%';
            $params['q_token'] = $like;
            $params['q_name'] = $like;
            $params['q_target'] = $like;
            $params['q_uname'] = $like;
            $params['q_uemail'] = $like;
        }

        $whereSql = '';
        if (!empty($where)) {
            $whereSql = ' WHERE ' . implode(' AND ', $where);
        }

        // count total
        $countSql = 'SELECT COUNT(*) as cnt FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id' . $whereSql;
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $countRow = $countStmt->fetch();
        $total = $countRow ? (int)$countRow['cnt'] : 0;

        $sql = 'SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.foreground, q.background, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id'
            . $whereSql
            . ' ORDER BY q.id DESC'
            . ' LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);

        // bind params (except limit/offset)
        foreach ($params as $k => $v) {
            $stmt->bindValue(is_int($k) ? $k + 1 : ':' . $k, $v);
        }

        // bind limit/offset as integers
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
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
                $row['foreground'] ?? null,
                $row['background'] ?? null,
                $createdAt,
                $row['owner_name'] ?? null,
                $row['owner_email'] ?? null
            );
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * {@inheritdoc}
     */
    public function findAllForUser(int $ownerUserId): array
    {
        $stmt = $this->pdo->prepare('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.foreground, q.background, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id WHERE owner_user_id = :owner ORDER BY q.id DESC');
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
                $row['foreground'] ?? null,
                $row['background'] ?? null,
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
        $stmt = $this->pdo->query('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.foreground, q.background, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id ORDER BY q.id DESC');
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
                $row['foreground'] ?? null,
                $row['background'] ?? null,
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
        $stmt = $this->pdo->prepare('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.foreground, q.background, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id WHERE q.id = :id');
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
            $row['foreground'] ?? null,
            $row['background'] ?? null,
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
        $stmt = $this->pdo->prepare('SELECT q.id, q.token, q.owner_user_id, q.target_url, q.name, q.foreground, q.background, q.created_at, u.name AS owner_name, u.email AS owner_email FROM qrcodes q LEFT JOIN users u ON q.owner_user_id = u.id WHERE q.token = :token');
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
            $row['foreground'] ?? null,
            $row['background'] ?? null,
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
        $stmt = $this->pdo->prepare('INSERT INTO qrcodes (token, owner_user_id, target_url, name, foreground, background) VALUES (:token, :owner, :target_url, :name, :foreground, :background)');
        $stmt->execute([
            'token' => $qrCode->getToken(),
            'owner' => $qrCode->getOwnerUserId(),
            'target_url' => $qrCode->getTargetUrl(),
            'name' => $qrCode->getName(),
            'foreground' => $qrCode->getForeground(),
            'background' => $qrCode->getBackground(),
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
            $qrCode->getForeground(),
            $qrCode->getBackground(),
            new \DateTimeImmutable(),
            $ownerName,
            $ownerEmail
        );
    }

    /**
     * {@inheritdoc}
     */
    public function update(QrCode $qrCode): QrCode
    {
        $stmt = $this->pdo->prepare('UPDATE qrcodes SET target_url = :target_url, name = :name, foreground = :foreground, background = :background WHERE id = :id');
        $stmt->execute([
            'target_url' => $qrCode->getTargetUrl(),
            'name' => $qrCode->getName(),
            'foreground' => $qrCode->getForeground(),
            'background' => $qrCode->getBackground(),
            'id' => $qrCode->getId(),
        ]);

        // return fresh from DB
        return $this->findOfId((int)$qrCode->getId());
    }
}
