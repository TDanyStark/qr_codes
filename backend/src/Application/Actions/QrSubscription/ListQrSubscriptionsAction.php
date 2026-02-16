<?php

declare(strict_types=1);

namespace App\Application\Actions\QrSubscription;

use Psr\Http\Message\ResponseInterface as Response;

class ListQrSubscriptionsAction extends QrSubscriptionAction
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

        $subs = $this->subscriptionRepository->listByQrCode($qrId);
        $userIds = array_map(static fn($sub) => $sub->getUserId(), $subs);

        return $this->respondWithData(['user_ids' => $userIds], 200);
    }
}
