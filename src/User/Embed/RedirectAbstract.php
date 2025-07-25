<?php

namespace Gems\User\Embed;

use Gems\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
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

    public function getRedirectUrl(
        ServerRequestInterface $request,
        DeferredRouteHelper $routeHelper,
        User $embeddedUser,
        User $deferredUser,
        string $patientId,
        array $organizations,
    ): RedirectResponse|string|null {
        return $routeHelper->getRouteUrl($this->getRedirectRouteName(), [], [], $deferredUser->getRole());
    }}