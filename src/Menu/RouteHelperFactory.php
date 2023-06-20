<?php

namespace Gems\Menu;

use Gems\Legacy\CurrentUserRepository;
use Gems\User\User;
use Laminas\Permissions\Acl\Acl;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class RouteHelperFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /**
         * @var Acl $acl
         */
        $acl = $container->get(Acl::class);

        /**
         * @var UrlHelper $urlHelper
         */
        $urlHelper = $container->get(UrlHelper::class);

        /**
         * @var array $config
         */
        $config = $container->get('config');

        $userRole = null;
        if ($container->has(CurrentUserRepository::class)) {
            /**
             * @var $currentUserRepository CurrentUserRepository
             */
            $currentUserRepository = $container->get(CurrentUserRepository::class);
            $currentUser = $currentUserRepository->getCurrentUser();
            if ($currentUser instanceof User) {
                $userRole = $currentUser->getRole();
            }
        }

        return new RouteHelper(
            $acl,
            $urlHelper,
            $userRole,
            $config,
        );
    }
}