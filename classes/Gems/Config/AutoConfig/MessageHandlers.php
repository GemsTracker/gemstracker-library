<?php

namespace Gems\Config\AutoConfig;

use ReflectionClass;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use RuntimeException;
class MessageHandlers implements AutoConfigInterface
{

    protected $attributeClass = AsMessageHandler::class;

    public function __invoke(ReflectionClass $reflectionClass, array $config): array
    {
        $attributes = $reflectionClass->getAttributes($this->attributeClass);
        $messengerConfig = [];
        if (isset($config['messenger'])) {
            $messengerConfig = $config['messenger'];
        }

        $newConfig = [
            'messenger' => [
                'buses' => [],
            ],
        ];

        foreach($attributes as $attribute) {
            $messageHandlerSettings = $attribute->newInstance();
            $newConfig = $this->registerHandler($reflectionClass, $messageHandlerSettings, $messengerConfig, $newConfig);
        }

        return $newConfig;
    }

    protected function getHandledClasses(ReflectionClass $reflectionClass, string $methodName): array
    {
        try {
            $reflectionMethod = $reflectionClass->getMethod($methodName);
        } catch (\ReflectionException) {
            throw new RuntimeException(sprintf('Invalid handler service "%s": class must have an "%s()" method.', $reflectionClass->getName(), $methodName));
        }

        if (0 === $reflectionMethod->getNumberOfRequiredParameters()) {
            throw new RuntimeException(sprintf('Invalid handler service "%s": method "%s::%s()" requires at least one argument, first one being the message it handles.', $reflectionClass->getName(), $reflectionClass->getName(), $methodName));
        }

        $parameters = $reflectionMethod->getParameters();

        /** @var \ReflectionNamedType|\ReflectionUnionType|null */
        $type = $parameters[0]->getType();

        if (!$type) {
            throw new RuntimeException(sprintf('Invalid handler service "%s": argument "$%s" of method "%s::%s()" must have a type-hint corresponding to the message class it handles.', $reflectionClass->getName(), $parameters[0]->getName(), $reflectionClass->getName(), $methodName));
        }

        if ($type instanceof \ReflectionUnionType) {
            $types = [];
            $invalidTypes = [];
            foreach ($type->getTypes() as $type) {
                if (!$type->isBuiltin()) {
                    $types[] = (string) $type;
                } else {
                    $invalidTypes[] = (string) $type;
                }
            }

            if ($types) {
                return ('__invoke' === $methodName) ? $types : array_fill_keys($types, $methodName);
            }

            throw new RuntimeException(sprintf('Invalid handler service "%s": type-hint of argument "$%s" in method "%s::__invoke()" must be a class , "%s" given.', $reflectionClass->getName(), $parameters[0]->getName(), $reflectionClass->getName(), implode('|', $invalidTypes)));
        }

        if ($type->isBuiltin()) {
            throw new RuntimeException(sprintf('Invalid handler service "%s": type-hint of argument "$%s" in method "%s::%s()" must be a class , "%s" given.', $reflectionClass->getName(), $parameters[0]->getName(), $reflectionClass->getName(), $methodName, $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type));
        }

        return ('__invoke' === $methodName) ? [$type->getName()] : [$type->getName() => $methodName];
    }

    protected function registerHandler(ReflectionClass $reflectionClass, object $settings, array $config, array $newConfig): array
    {
        $options = [];
        $buses = array_keys($config['buses']);
        if ($settings->bus !== null) {
            if (!in_array($settings->bus, $buses)) {
                throw new RuntimeException(sprintf('Invalid handler service "%s": bus "%s" does not exist (known ones are: "%s").', $reflectionClass->getName(), $settings->bus, join('", "', $buses)));
            }
            $options['bus'] = $settings->bus;
            $buses = [$settings->bus];
        }

        $handlerMethod = '__invoke';
        if ($settings->method !== null) {
            $handlerMethod = $settings->method;
        }
        $options['method'] = $handlerMethod;

        $handlesClasses = $settings->handles;
        if ($settings->handles === null) {
            $handlesClasses = $this->getHandledClasses($reflectionClass, $handlerMethod);
        }

        if ($settings->fromTransport !== null) {
            $options['from_transport'] = $settings->fromTransport;
        }

        if ($settings->fromTransport !== null) {
            $options['from_transport'] = $settings->fromTransport;
        }

        $options['priority'] = $settings->priority;

        foreach($buses as $bus) {
            if (!isset($newConfig['messenger']['buses'][$bus]['handlers'])) {
                $newConfig['messenger']['buses'][$bus]['handlers'] = [];
            }
            foreach((array)$handlesClasses as $handlesClass) {
                if (!isset($newConfig['messenger']['buses'][$bus]['handlers'][$handlesClass])) {
                    $newConfig['messenger']['buses'][$bus]['handlers'][$handlesClass] = [];
                }
                $newConfig['messenger']['buses'][$bus]['handlers'][$handlesClass][$reflectionClass->getName()] = $options;
            }
        }

        //print_r($newConfig);

        return $newConfig;
    }
}