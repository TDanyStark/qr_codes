<?php

declare(strict_types=1);

namespace App\Application\Actions\ReportSettings;

use Psr\Http\Message\ResponseInterface as Response;

class CreateReportSettingsAction extends ReportSettingsAction
{
    protected function action(): Response
    {
        $data = $this->normalizeInput();

        try {
            $settings = $this->buildSettings($data, null);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(['error' => $e->getMessage()], 400);
        }

        $saved = $this->settingsRepository->save($settings);

        if ($saved->isActive()) {
            try {
                $this->settingsRepository->setActive((int)$saved->getId());
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to set active report settings after create', [
                    'id' => $saved->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->respondWithData($saved, 201);
    }
}
