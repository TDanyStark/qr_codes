<?php

declare(strict_types=1);

namespace App\Application\Actions\ReportSettings;

use Psr\Http\Message\ResponseInterface as Response;

class UpdateReportSettingsAction extends ReportSettingsAction
{
    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');

        $existing = $this->settingsRepository->findById($id);
        if ($existing === null) {
            return $this->respondWithData(['error' => 'Report settings not found'], 404);
        }

        $data = $this->normalizeInput();

        try {
            $settings = $this->buildSettings($data, $existing);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(['error' => $e->getMessage()], 400);
        }

        $saved = $this->settingsRepository->save($settings);

        if ($saved->isActive()) {
            try {
                $this->settingsRepository->setActive((int)$saved->getId());
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to set active report settings after update', [
                    'id' => $saved->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->respondWithData($saved, 200);
    }
}
