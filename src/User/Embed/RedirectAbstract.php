<?php

namespace Gems\User\Embed;

use Zalt\Base\TranslatorInterface;

abstract class RedirectAbstract implements RedirectInterface
{
    /**
     * @var string|null The name of the initial route page
     */
    protected ?string $routeName = null;

    public function __construct(
        protected TranslatorInterface $translator,
    )
    {}

    public function getBaseMenuRouteName(): ?string
    {
        return $this->getRedirectRouteName();
    }

    /**
     * @return string|null redirect route
     */
    public function getRedirectRouteName(): ?string
    {
        return $this->routeName;
    }
}