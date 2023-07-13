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
    /**
     * @var array|string[]
     */
    protected array $defaultOverLoaderPaths;

    public function __construct(array $defaultOverLoaderPaths = ['Gems', 'MUtil'])
    {
        $this->defaultOverLoaderPaths = $defaultOverLoaderPaths;
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
        $overloader = new $requestedName($container, $overloaderPaths);
        $overloader->setDependencyResolver(new OrderedParamsContainerResolver());
        $overloader->legacyClasses = true;
        $overloader->legacyPrefix = 'Legacy';
        Model::setSource($overloader->createSubFolderOverloader('Model'));
        return $overloader;
    }
}
