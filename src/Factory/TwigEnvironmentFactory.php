<?php

namespace Gems\Factory;

use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigEnvironmentFactory extends \Mezzio\Twig\TwigEnvironmentFactory
{
    public function __invoke(ContainerInterface $container): Environment
    {
        $environment = parent::__invoke($container);
        $loader = $environment->getLoader();
        if ($loader instanceof FilesystemLoader) {
            $namespaces = $loader->getNamespaces();
            foreach($namespaces as $namespace) {
                $paths = $loader->getPaths($namespace);
                $loader->setPaths(array_reverse($paths), $namespace);
            }
        }

        return $environment;
    }
}