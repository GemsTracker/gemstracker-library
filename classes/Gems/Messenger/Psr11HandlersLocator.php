<?php

namespace Gems\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * PSR-11 handlers locator
 * See Symfony\Component\Messenger\Handler\HandlersLocator
 */
class Psr11HandlersLocator implements HandlersLocatorInterface
{
    /**
     * @param HandlerDescriptor[][]|callable[][] $handlers
     * @param ContainerInterface $container
     */
    public function __construct(private array $handlers, private ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlers(Envelope $envelope): iterable
    {
        $seen = [];

        foreach (self::listTypes($envelope) as $type) {
            foreach ($this->handlers[$type] ?? [] as $handlerDescriptor) {
                if (is_string($handlerDescriptor) && $this->container->has($handlerDescriptor)) {
                    $handlerDescriptor = $this->container->get($handlerDescriptor);
                }
                if (\is_callable($handlerDescriptor)) {
                    $handlerDescriptor = new HandlerDescriptor($handlerDescriptor);
                }

                if (!$this->shouldHandle($envelope, $handlerDescriptor)) {
                    continue;
                }

                $name = $handlerDescriptor->getName();
                if (\in_array($name, $seen)) {
                    continue;
                }

                $seen[] = $name;

                yield $handlerDescriptor;
            }
        }
    }

    /**
     * @internal
     */
    public static function listTypes(Envelope $envelope): array
    {
        $class = \get_class($envelope->getMessage());

        return [$class => $class]
            + class_parents($class)
            + class_implements($class)
            + ['*' => '*'];
    }

    private function shouldHandle(Envelope $envelope, HandlerDescriptor $handlerDescriptor): bool
    {
        if (null === $received = $envelope->last(ReceivedStamp::class)) {
            return true;
        }

        if (null === $expectedTransport = $handlerDescriptor->getOption('from_transport')) {
            return true;
        }

        return $received->getTransportName() === $expectedTransport;
    }
}