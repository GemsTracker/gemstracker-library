<?php

namespace Gems\Site;

class SiteUrl
{
    protected bool $active;

    protected bool $blocked;

    protected string $lang = 'en';

    protected array $organizations = [];

    protected string $style;

    protected string $url;

    public function getFirstOrganizationId(): int
    {
        return reset($this->organizations);
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @return int[]
     */
    public function getOrganizations(): array
    {
        return $this->organizations;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function isAllowedForOrganization(int $organizationId): bool
    {
        return in_array($organizationId, $this->organizations) || count($this->organizations) === 0;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
    }

}