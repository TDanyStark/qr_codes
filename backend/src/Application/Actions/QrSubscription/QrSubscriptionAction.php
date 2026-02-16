<?php

declare(strict_types=1);

namespace App\Application\Actions\QrSubscription;

use App\Application\Actions\Action;
use App\Domain\QrCode\QrCodeRepository;
use App\Domain\QrSubscription\QrSubscriptionRepository;
use Psr\Log\LoggerInterface;

abstract class QrSubscriptionAction extends Action
{
    protected QrSubscriptionRepository $subscriptionRepository;
    protected QrCodeRepository $qrCodeRepository;

    public function __construct(
        LoggerInterface $logger,
        QrSubscriptionRepository $subscriptionRepository,
        QrCodeRepository $qrCodeRepository
    ) {
        parent::__construct($logger);
        $this->subscriptionRepository = $subscriptionRepository;
        $this->qrCodeRepository = $qrCodeRepository;
    }

    /**
     * @return array{userId:int|null,isAdmin:bool}
     */
    protected function getAuthContext(): array
    {
        $jwt = $this->request->getAttribute('jwt');
        $userId = null;
        $isAdmin = false;
        if (is_object($jwt) && isset($jwt->sub)) {
            $userId = (int)$jwt->sub;
            $isAdmin = isset($jwt->rol) && $jwt->rol === 'admin';
        } elseif (is_array($jwt) && isset($jwt['sub'])) {
            $userId = (int)$jwt['sub'];
            $isAdmin = isset($jwt['rol']) && $jwt['rol'] === 'admin';
        }

        return ['userId' => $userId, 'isAdmin' => $isAdmin];
    }
}
