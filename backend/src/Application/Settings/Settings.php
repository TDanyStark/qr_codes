<?php

declare(strict_types=1);

namespace App\Application\Settings;

class Settings implements SettingsInterface
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return mixed
     */
    public function get(string $key = '')
    {
        if (empty($key)) {
            return $this->settings;
        }

        // support dot notation for nested settings, e.g. 'mail.from'
        $parts = explode('.', $key);
        $value = $this->settings;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }
}
