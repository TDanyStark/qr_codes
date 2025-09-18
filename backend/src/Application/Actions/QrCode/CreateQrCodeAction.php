<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Actions\Action;
use App\Application\Services\QrCode\QrCodeCreator;
use Psr\Http\Message\ResponseInterface as Response;

class CreateQrCodeAction extends QrCodeAction
{
    private QrCodeCreator $creator;

    public function __construct(\Psr\Log\LoggerInterface $logger, \App\Domain\QrCode\QrCodeRepository $qrCodeRepository, QrCodeCreator $creator)
    {
        parent::__construct($logger, $qrCodeRepository);
        $this->creator = $creator;
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

        return $this->respondWithData($result, 201);
    }
}
