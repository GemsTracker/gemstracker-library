<?php

namespace Gems\Handlers;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Site\SiteUtil;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;

class ChangeGroupHandler implements RequestHandlerInterface
{
    public const CURRENT_USER_GROUP_ATTRIBUTE = 'current_user_group';

    public function __construct(
        private readonly SiteUtil $siteUtil,
        private readonly UrlHelper $urlHelper,
        private readonly UserLoader $userLoader,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (isset($queryParams['group'])) {
            $this->setCurrentUserGroup($request, intval($queryParams['group']));
        }

        return new RedirectResponse($this->getUrl($request));
    }

    protected function getUrl(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_REFERER']) && $this->siteUtil->isAllowedUrl($serverParams['HTTP_REFERER'])) {
            return $serverParams['HTTP_REFERER'];
        }

        return $this->urlHelper->generate('home');
    }

    private function setCurrentUserGroup(ServerRequestInterface $request, int $groupId): void
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionInterface::class);

        $allowedGroups = $currentUser->getAllowedStaffGroups(false);

        $group = $this->userLoader->getGroup($groupId);

        if (!isset($allowedGroups[$groupId])) {
            if ($group->isActive()) {
                throw new \Gems\Exception($this->translator->trans('No access to group'), 403, null, sprintf(
                    $this->translator->trans('You are not allowed to switch to the %s group.'),
                    $group->getName()
                ));
            } else {
                throw new \Gems\Exception($this->translator->trans('No access to group'), 403, null, sprintf(
                    $this->translator->trans('You cannot switch to an inactive or non-existing group.')
                ));
            }
        }

        $session->set(self::CURRENT_USER_GROUP_ATTRIBUTE, $groupId);
        // $session->set('current_user_role',  $group->getRole());
    }
}
