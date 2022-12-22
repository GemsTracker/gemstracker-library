<?php


namespace Gems\Error;


use Gems\Log\ErrorLogger;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;

class ErrorLogEventListenerDelegatorFactory implements DelegatorFactoryInterface
{
    protected $errorLogger = ErrorLogger::class;

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
        if ($container->has($this->errorLogger)) {
            $listener->setErrorLog($container->get($this->errorLogger));
        }
        $errorHandler = $callback();
        $errorHandler->attachListener($listener);
        return $errorHandler;
    }
}
