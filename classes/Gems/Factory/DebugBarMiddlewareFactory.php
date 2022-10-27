<?php

namespace Gems\Factory;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\ExceptionsCollector;
use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DataCollector\TimeDataCollector;
use DebugBar\DebugBar;
use DebugBar\StandardDebugBar;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Middlewares\Debugbar as DebugbarMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class DebugBarMiddlewareFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $debugBar = new DebugBar();
        $debugBar->addCollector(new PhpInfoCollector());
        $debugBar->addCollector(new MessagesCollector());
        $debugBar->addCollector(new RequestDataCollector());
        $debugBar->addCollector(new TimeDataCollector());
        $debugBar->addCollector(new MemoryCollector());
        $debugBar->addCollector(new ExceptionsCollector());

        $debugBar->addCollector($container->get(ConfigCollector::class));

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        $streamFactory = $container->get(StreamFactoryInterface::class);

        return new DebugbarMiddleware($debugBar, $responseFactory, $streamFactory);
    }
}