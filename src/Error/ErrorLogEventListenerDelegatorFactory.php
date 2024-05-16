<?php


namespace Gems\Error;


use Gems\ConfigProvider;
use Gems\Log\Loggers;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

class ErrorLogEventListenerDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param callable $callback
     * @param array|null $options
     * @return ErrorHandler
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback, array $options = null)
    {
        $listener = new ErrorLogEventListener();
        if ($container->has(Loggers::class)) {
            $loggers = $container->get(Loggers::class);
            $errorLogger = $loggers->getLogger(ConfigProvider::ERROR_LOGGER);
            if ($errorLogger) {
                $listener->setErrorLog($errorLogger);
            }
        }
        $errorHandler = $callback();
        $errorHandler->attachListener($listener);
        return $errorHandler;
    }
}
