<?php

declare(strict_types=1);

namespace App\Application\Actions\QrCode;

use App\Application\Actions\Action;
use App\Domain\QrCode\QrCodeRepository;
use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;

abstract class QrCodeAction extends Action
{
    protected QrCodeRepository $qrCodeRepository;
    protected SettingsInterface $settings;

    public function __construct(LoggerInterface $logger, QrCodeRepository $qrCodeRepository, SettingsInterface $settings)
    {
        parent::__construct($logger);
        $this->qrCodeRepository = $qrCodeRepository;
        $this->settings = $settings;
    }

    /**
     * @param array|object|null $data
     * @return int[]|null
     */
    protected function parseSubscriberUserIds($data): ?array
    {
        if (is_array($data)) {
            if (!array_key_exists('subscriber_user_ids', $data)) {
                return null;
            }
            $raw = $data['subscriber_user_ids'];
        } elseif (is_object($data)) {
            if (!property_exists($data, 'subscriber_user_ids')) {
                return null;
            }
            $raw = $data->subscriber_user_ids;
        } else {
            return null;
        }

        if ($raw === null) {
            return [];
        }

        if (!is_array($raw)) {
            throw new \InvalidArgumentException('subscriber_user_ids must be an array');
        }

        $unique = [];
        foreach ($raw as $item) {
            $id = (int)$item;
            if ($id > 0) {
                $unique[$id] = true;
            }
        }

        return array_map('intval', array_keys($unique));
    }
}
