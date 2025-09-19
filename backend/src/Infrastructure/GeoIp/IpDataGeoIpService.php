<?php

declare(strict_types=1);

namespace App\Infrastructure\GeoIp;

use App\Application\Services\GeoIp\GeoIpServiceInterface;
use Psr\Log\LoggerInterface;

class IpDataGeoIpService implements GeoIpServiceInterface
{
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->apiKey = getenv('IPDATA_API_KEY') ?: '';
    }

    public function lookup(string $ip): array
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return [];
        }
        if ($this->apiKey === '') {
            // no API key configured -> skip
            return [];
        }
        $url = 'https://api.ipdata.co/' . rawurlencode($ip) . '?api-key=' . urlencode($this->apiKey);
        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 2.5,
                    'ignore_errors' => true,
                    'header' => [
                        'Accept: application/json'
                    ]
                ]
            ]);
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false) {
                return [];
            }
            $data = json_decode($raw, true);
            if (!is_array($data)) {
                return [];
            }
            return [
                'ip' => $data['ip'] ?? $ip,
                'city' => $data['city'] ?? null,
                'country' => $data['country_name'] ?? ($data['country'] ?? null),
                'country_code' => $data['country_code'] ?? null,
                'region' => $data['region'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('GeoIP lookup failed: ' . $e->getMessage());
            return [];
        }
    }
}
