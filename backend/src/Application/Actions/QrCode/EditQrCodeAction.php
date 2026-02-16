<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use App\Domain\QrCode\QrCodeRepository;
use App\Application\Settings\SettingsInterface;
use App\Application\Services\QrCode\QrWriterInterface;
use App\Application\Services\QrCode\FileStorageInterface;
use App\Application\Services\QrCode\QrColorParserInterface;
use App\Infrastructure\Utils\PublicDirectoryResolver;
use App\Application\Services\UrlBuilder;
use \App\Domain\QrCode\QrCode;
use App\Domain\QrSubscription\QrSubscriptionRepository;

class EditQrCodeAction extends QrCodeAction
{
    private QrWriterInterface $qrWriter;
    private FileStorageInterface $fileStorage;
    private QrColorParserInterface $colorParser;
    private PublicDirectoryResolver $publicResolver;
    private UrlBuilder $urlBuilder;
    private QrSubscriptionRepository $subscriptionRepository;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, SettingsInterface $settings, QrWriterInterface $qrWriter, FileStorageInterface $fileStorage, QrColorParserInterface $colorParser, PublicDirectoryResolver $publicResolver, UrlBuilder $urlBuilder, QrSubscriptionRepository $subscriptionRepository)
    {
        parent::__construct($logger, $qrCodeRepository, $settings);
        $this->qrWriter = $qrWriter;
        $this->fileStorage = $fileStorage;
        $this->colorParser = $colorParser;
        $this->publicResolver = $publicResolver;
        $this->urlBuilder = $urlBuilder;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    protected function action(): Response
    {
        $id = (int)$this->resolveArg('id');

        $data = $this->getFormData();
        $target = isset($data['target_url']) ? trim((string)$data['target_url']) : null;
        $name = isset($data['name']) ? trim((string)$data['name']) : null;
        $foreground = isset($data['foreground']) ? trim((string)$data['foreground']) : null;
        $background = isset($data['background']) ? trim((string)$data['background']) : null;

        try {
            $subscriberIds = $this->parseSubscriberUserIds($data);
        } catch (\InvalidArgumentException $e) {
            return $this->respondWithData(['error' => $e->getMessage()], 400);
        }

        if ($target === null && $name === null && $foreground === null && $background === null && $subscriberIds === null) {
            return $this->respondWithData(['error' => 'No fields to update'], 400);
        }

        try {
            $qr = $this->qrCodeRepository->findOfId($id);
        } catch (\Throwable $e) {
            return $this->respondWithData(['error' => 'QR not found'], 404);
        }

        // check ownership / admin
        $jwt = $this->request->getAttribute('jwt');
        $userId = null;
        $isAdmin = false;
        if (is_object($jwt) && isset($jwt->sub)) {
            $userId = (int)$jwt->sub;
            if (isset($jwt->rol) && $jwt->rol === 'admin') $isAdmin = true;
        }

        if (!$isAdmin && $userId !== $qr->getOwnerUserId()) {
            return $this->respondWithData(['error' => 'forbidden'], 403);
        }

        $newTarget = $target ?? $qr->getTargetUrl();
        $newName = $name ?? $qr->getName();
        $newForeground = $foreground ?? $qr->getForeground();
        $newBackground = $background ?? $qr->getBackground();

        $newQr = new QrCode(
            $qr->getId(),
            $qr->getToken(),
            $qr->getOwnerUserId(),
            $newTarget,
            $newName,
            $newForeground,
            $newBackground,
            $qr->getCreatedAt(),
            // updatedAt will be set by DB on update; preserve current value here
            $qr->getUpdatedAt(),
            $qr->getOwnerName(),
            $qr->getOwnerEmail()
        );
        try {
            $qr = $this->qrCodeRepository->update($newQr);
        } catch (\Throwable $e) {
            // log and continue
            $this->logger->error('Failed to update QR record: ' . $e->getMessage());
        }


        // Always regenerate images on edit. Parse provided colors or fall back to defaults.
        $fg = $foreground ?? '#000000';
        $bg = $background ?? '#ffffff';
        $fgColor = $this->colorParser->parseHexColor($fg);
        $bgColor = $this->colorParser->parseHexColor($bg);
        $regen = true;

        $token = $qr->getToken();
        $pngRel = '/tmp/qrcodes/' . $token . '.png';
        $svgRel = '/tmp/qrcodes/' . $token . '.svg';

        $links = [
            'png' => $pngRel,
            'svg' => $svgRel,
            'redirect' => $this->urlBuilder->buildRedirectUrl($token),
        ];

        if ($regen) {
            // delete existing files
            $publicDir = $this->publicResolver->getPublicDir();
            $pngFull = $publicDir . $pngRel;
            $svgFull = $publicDir . $svgRel;
            if (is_file($pngFull)) @unlink($pngFull);
            if (is_file($svgFull)) @unlink($svgFull);

            try {
                $generated = $this->qrWriter->generate($links['redirect'], $fgColor, $bgColor);
                // save via fileStorage
                $this->fileStorage->save(ltrim($pngRel, '/'), $generated['png']);
                $this->fileStorage->save(ltrim($svgRel, '/'), $generated['svg']);
                $links['png'] = $pngRel;
                $links['svg'] = $svgRel;
            } catch (\Throwable $e) {
                $this->logger->error('Failed to regenerate QR images: ' . $e->getMessage());
                return $this->respondWithData(['error' => 'failed to regenerate images'], 500);
            }
        }

        $data = [
            'qr' => $qr->toArray(),
            'links' => $links,
            'images_present' => true,
        ];

        if ($subscriberIds !== null) {
            $this->syncSubscriptions($qr->getId() ?? $id, $subscriberIds);
        }

        return $this->respondWithData($data, 200);
    }

    /**
     * @param int[] $subscriberIds
     */
    private function syncSubscriptions(int $qrId, array $subscriberIds): void
    {
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
    }
}
