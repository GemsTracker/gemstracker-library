<?php

declare(strict_types=1);

namespace Gems\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use MUtil\Model;
use Psr\Container\ContainerInterface;
use Zalt\Loader\DependencyResolver\OrderedParamsContainerResolver;
use Zalt\Loader\ProjectOverloader;

class ProjectOverloaderFactory implements FactoryInterface
{
    public function __construct(
        protected array $defaultOverLoaderPaths = ['Gems', 'MUtil'],
    )
    {
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /**
         * @var mixed[]
         */
        $config = $container->get('config');
        $overloaderPaths = $this->defaultOverLoaderPaths;
        if (isset($config['overLoaderPaths']) && is_array($config['overLoaderPaths'])) {
            $overloaderPaths = $config['overLoaderPaths'];
        }
        // if (isset($config['overLoaderPathsExtra']) && is_array($config['overLoaderPathsExtra'])) {
        dump($overloaderPaths);

        $overloader = new $requestedName($container, $overloaderPaths);
        $overloader->setDependencyResolver(new OrderedParamsContainerResolver());
        $overloader->legacyClasses = true;
        $overloader->legacyPrefix = 'Legacy';
        Model::setSource($overloader->createSubFolderOverloader('Model'));
        return $overloader;
    }
}
