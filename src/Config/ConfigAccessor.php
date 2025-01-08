<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Config
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Config;

/**
 * @package    Gems
 * @subpackage Config
 * @since      Class available since version 1.0
 */
class ConfigAccessor
{
    public function __construct(
        protected readonly array $config,
    )
    { }

    public function getArray(): array
    {
        return $this->config;
    }

    public function getDefaultLocale(): string
    {
        return $this->config['locale']['default'] ?? 'en';
    }

    /**
     * @return array<string, string> locale string => locale description
     */
    public function getLocales(): array
    {
        $locales = $this->config['locale']['locales'] ?? ['en', 'nl'];
        $output  = [];

        foreach ($locales as $locale) {
            $output[$locale] = \Locale::getDisplayLanguage($locale);
        }

        return $output;
    }

    public function hasTFAMethod(string $method): bool
    {
        if (isset($this->config['twofactor']['methods'][$method])) {
            return ! ($this->config['twofactor']['methods'][$method]['disabled'] ?? false);
        }
        return false;
    }

    public function isAutosearch(): bool
    {
        return $this->config['interface']['autosearch'] ?? false;
    }

    /**
     * Is login shared between organizations (which therefore require
     * a unique staff login id for each user, instead of for each
     * user within an organization).
     *
     * @return boolean
     */
    public function isLoginShared(): bool
    {
        return $this->config['organization']['sharedLogin'] ?? false;
    }
}