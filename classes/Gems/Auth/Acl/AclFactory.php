<?php

namespace Gems\Auth\Acl;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AclFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var AclRepository $repository */
        $repository = $container->get(AclRepository::class);

        return $repository->getAcl();
    }
}
