<?php

namespace Gems\Factory;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

class ReflectionAbstractFactory implements AbstractFactoryInterface
{

    /**
     * Allows overriding the internal list of aliases. These should be of the
     * form `class name => well-known service name`; see the documentation for
     * the `$aliases` property for details on what is accepted.
     *
     * @param string[] $aliases
     */
    public function __construct(array $aliases = [])
    {
        if (! empty($aliases)) {
            $this->aliases = $aliases;
        }
    }

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $reflector = new ReflectionClass($requestedName);

        if (! $reflector->isInstantiable()) {
            throw new ServiceNotCreatedException("Target class $requestedName is not instantiable.");
        }

        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            return new $requestedName;
        }

        $askedDependencies = $constructor->getParameters();

        $results = $this->resolveDependencies($askedDependencies, $container);

        return new $requestedName(...$results);
    }

    /**
     * {@inheritDoc}
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return class_exists($requestedName);
    }

    protected function resolveDependencies(array $askedDependencies, ContainerInterface $container)
    {
        return array_map(function(ReflectionParameter $dependency) use ($container) {
            $dependencyType = $dependency->getType();

            $dependencyName = null;
            if ($dependencyType instanceof \ReflectionNamedType) {
                $dependencyName = $dependencyType->getName();
            }

            // Check for aliases
            if (isset($this->aliases[$dependencyName])) {
                $dependencyName = $this->aliases[$dependencyName];
            }

            // return container if asked
            if ($dependencyName === ContainerInterface::class) {
                return $container;
            }
            
            if (null !== $dependencyName) {
                // Return named parameter
                if ($container->has($dependencyName)) {
                    return $container->get($dependencyName);
                }

                // Returned aliased service
                if ($container->has($dependency->getName())) {
                    return $container->get($dependency->getName());
                }
            }
            
            // Try the default value
            return $this->resolveDefault($dependency);
        }, $askedDependencies);
    }

    /**
     * If a construct parameters do not have a class declaration, see if it has a default value,
     * otherwise it can't be loaded
     *
     * @param ReflectionParameter $parameter
     * @return mixed Default value
     * @throws ServiceNotFoundException Dependency can't be resolved
     */
    protected function resolveDefault(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ServiceNotFoundException("Dependency [$parameter->name] can't be resolved in class {$parameter->getDeclaringClass()->getName()}");
    }
}