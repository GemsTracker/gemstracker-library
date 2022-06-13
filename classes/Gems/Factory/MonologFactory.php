<?php

declare(strict_types=1);


namespace Gems\Factory;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\Db\Adapter\Adapter;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RedisHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use \Redis;

class MonologFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        if (isset($config['log'], $config['log'][$requestedName])) {
            $logConfig = $config['log'][$requestedName];

            $logger = new Logger($requestedName);

            foreach($logConfig['writers'] as $handlerName => $handlerConfig) {
                $priority = Level::Debug;
                if (isset($handlerConfig['priority'])) {
                    $priority = $handlerConfig['priority'];
                }
                $handler = null;
                switch($handlerName) {
                    case 'redis':
                        $key = $requestedName;
                        if (isset($handlerConfig['key'])) {
                            $key = $handlerConfig['key'];
                        }
                        if (isset($handlerConfig['clientClass']) && $container->has($handlerConfig['clientClass'])) {
                            $redis = $container->get($handlerConfig['clientClass']);
                        } elseif ($container->has(Redis::class)) {
                            $redis = $container->get(Redis::class);
                        }

                        if ($redis instanceof Redis) {
                            $handler = new RedisHandler($redis, $key, $priority);
                        }
                        break;
                    /*case 'db':
                        if (isset($handlerConfig['class']) && $container->has($handlerConfig['class'])) {
                            $db = $container->has($handlerConfig['class']);
                        } elseif ($container->has(Adapter::class)) {
                            $db = $container->get(Adapter::class);
                        }
                        $table = 'user_logs';
                        if (isset($handlerConfig['table'])) {
                            $table = $handlerConfig['table'];
                        }
                        $handler = new LaminasDbHandler($db, $table, $priority);
                        break;*/
                    case 'stream':
                    default:
                        $stream = 'data/logs';
                        if (isset($handlerConfig['options'], $handlerConfig['options']['stream'])) {
                            $stream = $handlerConfig['options']['stream'];
                        }
                        $handler = new StreamHandler($stream, $priority);
                        break;
                }
                if ($handler instanceof HandlerInterface) {

                    if (isset($handlerConfig['options'], $handlerConfig['options']['processors'])) {
                        $processors = $this->getProcessors($handlerConfig['options']['processors']);
                        if ($processors) {
                            foreach ($processors as $processor) {
                                $handler->pushProcessor($processor);
                            }
                        }
                    }
                    if (isset($handlerConfig['formatter'], $handlerConfig['formatter']['name'])) {
                        if (class_exists($handlerConfig['formatter']['name'])) {
                            $formatterClass = $handlerConfig['formatter']['name'];
                            $options = [];
                            if (isset($processorConfig['options'])) {
                                $options = $processorConfig['options'];
                            }
                            $handler->setFormatter(new $formatterClass(...$options));
                        }
                    }

                    $logger->pushHandler($handler);
                }

            }
            if (isset($logConfig['processors'])) {
                $processors = $this->getProcessors($logConfig['processors']);
                if ($processors) {
                    foreach ($processors as $processor) {
                        $logger->pushProcessor($processor);
                    }
                }
            }
            return $logger;
        }
    }

    /**
     * Get processor classes from the ones in the log config
     *
     * @param array $processorConfigs
     * @return array|null
     */
    protected function getProcessors(array $processorConfigs)
    {
        $processors = [];
        foreach($processorConfigs as $processorConfig) {
            if (class_exists($processorConfig['name'])) {
                $processorClass = $processorConfig['name'];
                $options = [];
                if (isset($processorConfig['options'])) {
                    $options = $processorConfig['options'];
                }
                $processors[] = new $processorClass(...$options);
            }
        }
        if (count($processors)) {
            return $processors;
        }
        return null;
    }
}
