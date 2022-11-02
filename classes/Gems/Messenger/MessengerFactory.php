<?php

namespace Gems\Messenger;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\AddBusNameStampMiddleware;
use Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware;
use Symfony\Component\Messenger\Middleware\FailedMessageProcessingMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\RejectRedeliveredMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;

class MessengerFactory implements FactoryInterface
{

    private string $busName;

    public function __construct(string $busName = 'messenger.bus.default')
    {
        $this->busName = $busName;
    }
    
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): MessageBusInterface
    {
        $config = $container->get('config');
        $customMiddleware = [];

        if (!isset($config['messenger'], $config['messenger']['buses'], $config['messenger']['buses'][$this->busName])) {
            throw new \Exception('No message bus config found');
        }
        if (isset($config['messenger']['buses'][$this->busName]['middleware'])) {
            $customMiddleware = $config['messenger']['buses'][$this->busName]['middleware'];
        }

        $eventDispatcher = $container->get(EventDispatcherInterface::class);

        $sendMessageLocator = $this->getSendMessageLocator($config['messenger']['buses'][$this->busName], $container);
        $sendMessageMiddleware = new SendMessageMiddleware($sendMessageLocator, $eventDispatcher);

        $allowNoHandlers = false;
        if (isset($config['messenger']['buses'][$this->busName]['allowNoHandlers'])) {
            $allowNoHandlers = $config['messenger']['buses'][$this->busName]['allowNoHandlers'];
        }

        $handleMessageLocator = $this->getHandleMessageLocator($config['messenger']['buses'][$this->busName], $container);
        $handleMessageMiddleware = new HandleMessageMiddleware($handleMessageLocator, $allowNoHandlers);

        if ($container->has('messengerLogger')) {
            $logger = $container->get('messengerLogger');
            $sendMessageMiddleware->setLogger($logger);
            $handleMessageMiddleware->setLogger($logger);
        }

        $middleware = [
            new AddBusNameStampMiddleware($this->busName),
            new RejectRedeliveredMessageMiddleware(),
            new DispatchAfterCurrentBusMiddleware(),
            new FailedMessageProcessingMiddleware(),
            ...$customMiddleware,
            $sendMessageMiddleware,
            $handleMessageMiddleware,
        ];

        return new MessageBus($middleware);
    }

    protected function getHandleMessageLocator(array $config, ContainerInterface $container): Psr11HandlersLocator
    {
        if (!isset($config['handlers'])) {
            throw new \Exception(sprintf('No handlers found for bus %s', $this->busName));
        }
        $handlers = [];
        foreach($config['handlers'] as $type=>$handler) {
            $handlers[$type] = (array)$handler;
        }
        return new Psr11HandlersLocator($handlers, $container);
    }

    protected function getSendMessageLocator(array $config, ContainerInterface $container): SendersLocator
    {
        if (!isset($config['routes'])) {
            throw new \Exception(sprintf('No routes found for bus %s', $this->busName));
        }
        $routes = [];
        foreach($config['routes'] as $type=>$route) {
            $routes[$type] = (array)$route;
        }
        return new SendersLocator($routes, $container);
    }
}