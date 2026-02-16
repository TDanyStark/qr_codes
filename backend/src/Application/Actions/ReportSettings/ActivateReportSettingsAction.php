<?php

declare(strict_types=1);

namespace App\Application\Actions\ReportSettings;

use Psr\Http\Message\ResponseInterface as Response;

class ActivateReportSettingsAction extends ReportSettingsAction
{
    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');

        $existing = $this->settingsRepository->findById($id);
        if ($existing === null) {
            return $this->respondWithData(['error' => 'Report settings not found'], 404);
        }

        $this->settingsRepository->setActive($id);

        return $this->respondWithData(['id' => $id, 'active' => true], 200);
    }
}
