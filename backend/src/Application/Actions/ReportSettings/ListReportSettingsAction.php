<?php

declare(strict_types=1);

namespace App\Application\Actions\ReportSettings;

use Psr\Http\Message\ResponseInterface as Response;

class ListReportSettingsAction extends ReportSettingsAction
{
    protected function action(): Response
    {
        $items = $this->settingsRepository->listAll();
        return $this->respondWithData($items);
    }
}
