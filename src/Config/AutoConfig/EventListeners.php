<?php

namespace Gems\Config\AutoConfig;

use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use RuntimeException;
class EventListeners implements AutoConfigInterface
{

    protected $attributeClass = AsEventListener::class;

    public function __invoke(ReflectionClass $reflectionClass, array $config): array
    {
        $attributes = $reflectionClass->getAttributes($this->attributeClass);

        $newConfig = [
            'events' => [
                'listeners' => [],
            ],
        ];

        foreach($attributes as $attribute) {
            $eventListenerSettings = $attribute->newInstance();
            $newConfig = $this->registerEventListener($reflectionClass, $eventListenerSettings, $newConfig);
        }

        return $newConfig;
    }

    protected function getEventClasses(ReflectionClass $reflectionClass, ?string $methodName): array
    {
        if ($methodName === null) {
            $methodName = '__invoke';
        }
        try {
            $reflectionMethod = $reflectionClass->getMethod($methodName);
        } catch (\ReflectionException) {
            throw new RuntimeException(sprintf('Invalid listener class "%s": class must have an "%s()" method.', $reflectionClass->getName(), $methodName));
        }

        if (0 === $reflectionMethod->getNumberOfRequiredParameters()) {
            throw new RuntimeException(sprintf('Invalid listener class "%s": method "%s::%s()" requires at least one argument, first one being the event it handles.', $reflectionClass->getName(), $reflectionClass->getName(), $methodName));
        }

        $parameters = $reflectionMethod->getParameters();

        /** @var \ReflectionNamedType|\ReflectionUnionType|null */
        $type = $parameters[0]->getType();

        if (!$type) {
            throw new RuntimeException(sprintf('Invalid listener class "%s": argument "$%s" of method "%s::%s()" must have a type-hint corresponding to the event class it handles.', $reflectionClass->getName(), $parameters[0]->getName(), $reflectionClass->getName(), $methodName));
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

            throw new RuntimeException(sprintf('Invalid listener class "%s": type-hint of argument "$%s" in method "%s::__invoke()" must be a class , "%s" given.', $reflectionClass->getName(), $parameters[0]->getName(), $reflectionClass->getName(), implode('|', $invalidTypes)));
        }

        if ($type->isBuiltin()) {
            throw new RuntimeException(sprintf('Invalid listener class "%s": type-hint of argument "$%s" in method "%s::%s()" must be a class , "%s" given.', $reflectionClass->getName(), $parameters[0]->getName(), $reflectionClass->getName(), $methodName, $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type));
        }

        return ('__invoke' === $methodName) ? [$type->getName()] : [$type->getName() => $methodName];
    }

    protected function registerEventListener(ReflectionClass $reflectionClass, object $settings, array $newConfig): array
    {
        $listener = $reflectionClass->getName();
        if ($settings->method !== null && $settings->method !== '__invoke') {
            $listenerMethod = $settings->method;
            $listener = [$reflectionClass->getName(), $listenerMethod];
        }

        $priority = $settings->priority;

        $events = $settings->event;
        if ($events === null) {
            $events = $this->getEventClasses($reflectionClass, $settings->method);
        }

        if ($settings->dispatcher !== null) {
            // TODO: support multiple dispatchers
        }

        foreach((array)$events as $event) {
            $newConfig['events']['listeners'][$event] = [$listener, $priority];
        }

        return $newConfig;
    }
}