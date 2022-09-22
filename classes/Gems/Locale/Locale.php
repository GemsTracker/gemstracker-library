<?php

namespace Gems\Locale;

use MUtil\Model;

class Locale
{
    private array $config;

    private string $currentLanguage;

    public function __construct(array $config)
    {
        if (isset($config['locale'])) {
            $this->config = $config['locale'];
        }
    }

    public function getAvailableLanguages(): array
    {
        if (isset($this->config, $this->config['availableLocales'])) {
            return $this->config['availableLocales'];
        }
        return [];
    }

    public function getDefaultLanguage(): string
    {
        if (isset($this->config, $this->config['default'])) {
            return $this->config['default'];
        }
        return 'en';
    }

    public function getUrlHash(string $url): string
    {
        return urlencode(base64_encode($url));
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

    public function getModelTypeDefaults($language = null): array
    {
        if ($language === null) {
            $language = $this->getCurrentLanguage();
        }

        $typeDefaults = [];

        if (isset($this->config['defaultTypes'])) {
            $typeDefaults = $this->config['defaultTypes'];
        }

        if (isset($this->config['localeTypes'][$language])) {
            foreach($this->config['localeTypes'][$language] as $modelType => $settings) {
                foreach($settings as $settingName => $value) {
                    $typeDefaults[$modelType][$settingName] = $value;
                }
            }
        }

        return $typeDefaults;
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

        // TODO: Remove state
        Model::addTypesDefaults($this->getModelTypeDefaults());
    }
}
