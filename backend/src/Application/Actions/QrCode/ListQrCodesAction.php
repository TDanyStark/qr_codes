<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use Psr\Http\Message\ResponseInterface as Response;

class ListQrCodesAction extends QrCodeAction
{
    protected function action(): Response
    {
        $jwt = $this->request->getAttribute('jwt');

        $userId = null;
        if (is_array($jwt) && isset($jwt['sub'])) {
            $userId = (int)$jwt['sub'];
        } elseif (is_object($jwt) && isset($jwt->sub)) {
            $userId = (int)$jwt->sub;
        }

        if ($userId === null) {
            // no user id in token -> return empty
            return $this->respondWithData([], 200);
        }

        // check role: admin can see all
        $isAdmin = false;
        if (is_array($jwt) && isset($jwt['rol']) && $jwt['rol'] === 'admin') {
            $isAdmin = true;
        } elseif (is_object($jwt) && isset($jwt->rol) && $jwt->rol === 'admin') {
            $isAdmin = true;
        }

        if ($isAdmin) {
            $items = $this->qrCodeRepository->findAll();
        } else {
            $items = $this->qrCodeRepository->findAllForUser($userId);
        }

        // build base url from env and ensure no trailing slash
        $baseUrl = getenv('URL_BASE') ?: '';
        $baseUrl = rtrim($baseUrl, '/');

        $urlBaseToken = ($baseUrl !== '' ? $baseUrl : '') . '/r/';

        $response = [
            'items' => $items,
            'url_base_token' => $urlBaseToken,
        ];

        return $this->respondWithData($response);
    }
}
