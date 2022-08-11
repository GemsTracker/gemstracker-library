<?php

namespace Gems\Locale;

use MUtil\Model;

class Locale
{
    private array $config;

    private string $currentLanguage;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getDefaultLanguage(): string
    {
        if (isset($this->config['locale'], $this->config['locale']['default'])) {
            return $this->config['locale']['default'];
        }
        return 'en';
    }

    /**
     * @return string
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->getCurrentLanguage();
    }

    public function isCurrentLanguageDefault(): bool
    {
        return ($this->currentLanguage === $this->getDefaultLanguage());
    }

    /**
     * @param string $currentLanguage
     */
    public function setCurrentLanguage(string $currentLanguage): void
    {
        $this->currentLanguage = $currentLanguage;
        
        $config = $this->config['locale'] ?? [];
        
        if (isset($config['defaultTypes'])) {
            $settings = $config['defaultTypes'];
        }
        if (isset($config['localeTypes'][$currentLanguage])) {
            if (isset($settings)) {
                foreach ($config['defaultTypes'][$currentLanguage] as $type => $settings) {
                    foreach ($settings as $key => $value) {
                        // Set each value seperately, each overrules existing settings.
                        $settings[$type][$key] = $value;
                    }
                }
            } else {
                $settings = $this->config['localeTypes'][$currentLanguage];
            }
        }
        
        if (isset($settings)) {
            Model::addTypesDefaults($settings);
        }
    }
}
