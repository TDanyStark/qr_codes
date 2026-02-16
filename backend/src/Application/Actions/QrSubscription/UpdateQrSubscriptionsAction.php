<?php

declare(strict_types=1);

namespace App\Application\Actions\QrSubscription;

use Psr\Http\Message\ResponseInterface as Response;

class UpdateQrSubscriptionsAction extends QrSubscriptionAction
{
    protected function action(): Response
    {
        $qrId = (int)$this->resolveArg('id');

        try {
            $qr = $this->qrCodeRepository->findOfId($qrId);
        } catch (\Throwable $e) {
            return $this->respondWithData(['error' => 'QR not found'], 404);
        }

        $auth = $this->getAuthContext();
        if ($auth['userId'] === null) {
            return $this->respondWithData(['error' => 'unauthenticated'], 401);
        }

        if (!$auth['isAdmin'] && $auth['userId'] !== $qr->getOwnerUserId()) {
            return $this->respondWithData(['error' => 'forbidden'], 403);
        }

        $input = $this->getFormData();
        $data = is_array($input) ? $input : (is_object($input) ? (array)$input : []);

        if (!array_key_exists('subscriber_user_ids', $data)) {
            return $this->respondWithData(['error' => 'subscriber_user_ids is required'], 400);
        }

        $raw = $data['subscriber_user_ids'];
        if ($raw === null) {
            $raw = [];
        }
        if (!is_array($raw)) {
            return $this->respondWithData(['error' => 'subscriber_user_ids must be an array'], 400);
        }

        $unique = [];
        foreach ($raw as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $unique[$id] = true;
            }
        }
        $subscriberIds = array_map('intval', array_keys($unique));

        $current = $this->subscriptionRepository->listByQrCode($qrId);
        $currentIds = array_map(static fn($sub) => $sub->getUserId(), $current);

        $toAdd = array_values(array_diff($subscriberIds, $currentIds));
        $toRemove = array_values(array_diff($currentIds, $subscriberIds));

        foreach ($toRemove as $userId) {
            try {
                $this->subscriptionRepository->delete($qrId, (int)$userId);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to remove QR subscription', [
                    'qrcode_id' => $qrId,
                    'user_id' => (int)$userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

        return $this->respondWithData(['user_ids' => $subscriberIds], 200);
    }
}
