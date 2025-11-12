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

    public function canTfaBeDisabled(): bool
    {
        return $this->config['twofactor']['allowAuthenticatorRemoval'] ?? false;
    }

    /**
     * Extends the execution time for the application
     *
     * @return void
     */
    public function extendBatchLoadTime(): void
    {
        $mimimumTime = $this->config['interface']['minimumBatchWait'] ?? 500;
        if (ini_get('max_execution_time ') < $mimimumTime) {
            set_time_limit($mimimumTime);
        }
    }
    public function getAfterTrackChangeDefaultRoute(): string
    {
        return $this->config['survey']['tracks']['afterChangeRoute'] ?? 'respondent.tracks.show';
    }

    public function getAllowedSites(): array
    {
        if (isset($this->config['sites'], $this->config['sites']['allowed'])) {
            return array_column($this->config['sites']['allowed'], 'url');
        }
        return [];
    }

    public function getAppName(): string
    {
        return $this->config['app']['name'] ?? 'GemsTracker';
    }

    public function getArray(): array
    {
        return $this->config;
    }

    public function getAuthTemplate(): string
    {
        return $this->config['auth']['loginTemplate'] ?? 'gems::login';
    }

    public function getDefaultLocale(): string
    {
        return $this->config['locale']['default'] ?? 'en';
    }

    public function getDefaultTfaRequired(): bool
    {
        return $this->config['twofactor']['requireAuthenticatorTotp'] ?? true;
    }

    public function getDumpFile():? string
    {
        return $this->config['dump-to'] ?? null;
    }

    public function getHome(): string
    {
        return $this->config['interface']['home'] ?? 'auth.login';
    }

    public function getLdapServers(): array
    {
        return $this->config['ldap'] ?? [];
    }

    /**
     * @return array<string, string> locale string => locale description
     */
    public function getLocales(): array
    {
        $locales = $this->config['locale']['availableLocales'] ?? ['en', 'nl'];
        $output  = [];

        foreach ($locales as $locale) {
            $output[$locale] = \Locale::getDisplayLanguage($locale);
        }

        return $output;
    }

    public function getSessionMaxAwayTime(): int
    {
        return (int) $this->config['session']['max_away_time'];
    }

    public function getSessionMaxIdleTime(): int
    {
        return (int) $this->config['session']['max_idle_time'];
    }

    public function getSessionMaxTotalTime(): int
    {
            return (int) $this->config['session']['max_total_time'];
    }

    public function getTFAMethods(): array
    {
        $enabledMethods = array_keys($this->config['twofactor']['methods']);

        if ($this->config['twofactor']['requireAuthenticatorTotp']) {
            $enabledMethods = array_intersect($enabledMethods, ['AuthenticatorTotp']);
        }
        foreach ($enabledMethods as $i => $method) {
            if (! $this->hasTFAMethod($method)) {
                unset($enabledMethods[$i]);
            }
        }
        return $enabledMethods;
    }

    public function hasLdap(): bool
    {
        if (isset($this->config['ldap'])) {
            return (bool) $this->config['ldap'];
        }
        return false;
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

    public function isSurveyDurationCalculationEnabled(): bool
    {
        if (isset($this->config['survey']['details']['duration'])) {
            return $this->config['survey']['details']['duration'] !== false;
        }
        return true;
    }

    public function isSurveyUsageCalculationEnabled(): bool
    {
        if (isset($this->config['survey']['details']['usage'])) {
            return $this->config['survey']['details']['usage'] !== false;
        }
        return true;
    }
}