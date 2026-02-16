<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Services\QrCode\QrCodeCreator;
use App\Domain\QrSubscription\QrSubscriptionRepository;
use App\Domain\QrCode\QrCodeRepository;
use App\Application\Settings\SettingsInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class CreateQrCodeAction extends QrCodeAction
{
    private QrCodeCreator $creator;
    private QrSubscriptionRepository $subscriptionRepository;

    public function __construct(
        LoggerInterface $logger,
        QrCodeRepository $qrCodeRepository,
        SettingsInterface $settings,
        QrCodeCreator $creator,
        QrSubscriptionRepository $subscriptionRepository
    )
    {
        parent::__construct($logger, $qrCodeRepository, $settings);
        $this->creator = $creator;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    protected function action(): Response
    {
        $data = $this->getFormData();

        if (empty($data['target_url'] ?? null)) {
            return $this->respondWithData(['error' => 'target_url is required'], 400);
        }

        // get user id from jwt
        $jwt = $this->request->getAttribute('jwt');
        $userId = null;
        if (is_array($jwt) && isset($jwt['sub'])) {
            $userId = (int)$jwt['sub'];
        } elseif (is_object($jwt) && isset($jwt->sub)) {
            $userId = (int)$jwt->sub;
        }

        if ($userId === null) {
            return $this->respondWithData(['error' => 'unauthenticated'], 401);
        }

        try {
            $result = $this->creator->createFromData($data, $userId);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(['error' => $e->getMessage()], 400);
        }

        try {
            $subscriberIds = $this->parseSubscriberUserIds($data);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(['error' => $e->getMessage()], 400);
        }

        if ($subscriberIds !== null) {
            $qr = $result['qr'] ?? null;
            $qrId = is_object($qr) && method_exists($qr, 'getId') ? (int)$qr->getId() : null;
            if ($qrId !== null) {
                $this->syncSubscriptions($qrId, $subscriberIds);
            }
        }

        return $this->respondWithData($result, 201);
    }

    /**
     * @param int[] $subscriberIds
     */
    private function syncSubscriptions(int $qrId, array $subscriberIds): void
    {
        $current = $this->subscriptionRepository->listByQrCode($qrId);
        $currentIds = array_map(static fn($sub) => $sub->getUserId(), $current);

        $toAdd = array_values(array_diff($subscriberIds, $currentIds));

        foreach ($toAdd as $userId) {
            try {
                $this->subscriptionRepository->create($qrId, (int)$userId);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to add QR subscription', [
                    'qrcode_id' => $qrId,
                    'user_id' => (int)$userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
