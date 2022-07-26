<?php

namespace Gems\Locale;

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
    }
}