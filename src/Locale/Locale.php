<?php

namespace Gems\Locale;

use Zalt\Base\SymfonyTranslator;
use Zalt\Base\TranslatorInterface;

class Locale
{
    private array $config;

    private string $currentLanguage;

    private bool $isDefaultLanguage;

    private ?TranslatorInterface $translator = null;

    public function __construct(array $config)
    {
        if (isset($config['locale'])) {
            $this->config = $config['locale'];
        }

        $this->setCurrentLanguage($this->getDefaultLanguage());
        $this->isDefaultLanguage = true;
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
        return $this->isDefaultLanguage;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param string $currentLanguage
     */
    public function setCurrentLanguage(string $currentLanguage): void
    {
        \Locale::setDefault($currentLanguage);
        \setlocale(LC_ALL, $currentLanguage);
        $this->currentLanguage = $currentLanguage;
        $this->isDefaultLanguage = false;

        if ($this->translator instanceof SymfonyTranslator) {
            $this->translator->setLocale($currentLanguage);
        }
    }
}
