<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Services\UrlBuilder;

use Psr\Log\LoggerInterface;
use App\Domain\QrCode\QrCodeRepository;
use App\Application\Settings\SettingsInterface;

class ListQrCodesAction extends QrCodeAction
{
    private UrlBuilder $urlBuilder;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, SettingsInterface $settings, UrlBuilder $urlBuilder)
    {
        parent::__construct($logger, $qrCodeRepository, $settings);
        $this->urlBuilder = $urlBuilder;
    }
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
        if (is_object($jwt) && isset($jwt->rol) && $jwt->rol === 'admin') {
            $isAdmin = true;
        }

        // read pagination and query params from GET
        $params = $this->request->getQueryParams();

        $page = isset($params['page']) ? (int)$params['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $query = isset($params['query']) ? trim((string)$params['query']) : null;

        // per-page: prefer query param, then settings/env, fallback to default 10
        $perPage = isset($params['per_page']) ? (int)$params['per_page'] : (int)($this->settings->get('pagination.per_page') ?? getenv('PER_PAGE') ?? getenv('PERPAGE') ?? 10);
        if ($perPage < 1) {
            $perPage = 10;
        }
        // clamp to a reasonable maximum to avoid very large requests
        $maxPerPage = 100;
        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        // fetch paginated results
        if ($isAdmin) {
            $result = $this->qrCodeRepository->list($page, $perPage, $query, null);
        } else {
            $result = $this->qrCodeRepository->list($page, $perPage, $query, $userId);
        }

        $items = $result['items'] ?? [];
        $total = $result['total'] ?? 0;

        $urlBaseToken = $this->urlBuilder->getUrlBaseToken();

        $response = [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int)ceil($total / $perPage),
            ],
            'url_base_token' => $urlBaseToken,
        ];

        return $this->respondWithData($response);
    }
}
