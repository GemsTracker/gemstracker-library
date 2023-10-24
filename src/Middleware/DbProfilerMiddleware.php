<?php

namespace Gems\Middleware;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Profiler\Profiler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DbProfilerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {}


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $test = $this->container->get(Adapter::class);

        if (isset($_ENV['DB_PROFILE']) && $_ENV['DB_PROFILE'] === '1') {
            /**
             * @var Adapter $dbAdapter
             */
            $dbAdapter = $this->container->get(Adapter::class);
            $profiler = $dbAdapter->getProfiler();
            if ($profiler instanceof Profiler) {
                $profiles = $profiler->getProfiles();
                foreach($profiles as $profile) {
                    $profile = ['type' => $dbAdapter::class] + $profile;
                    dump($profile);
                }
            }

            /**
             * @var \Zend_Db_Adapter_Abstract $legacyAdapter
             */
            $legacyAdapter = $this->container->get(\Zend_Db_Adapter_Abstract::class);
            $profiler = $legacyAdapter->getProfiler();
            $profiles = $profiler->getQueryProfiles();
            if ($profiles) {
                foreach ($profiles as $profile) {
                    if ($profile instanceof \Zend_Db_Profiler_Query) {
                        $profileInfo = [
                            'type' => $legacyAdapter::class,
                            'sql' => str_replace('\n', '', $profile->getQuery()),
                            'parameters' => $profile->getQueryParams(),
                            'start' => $profile->getStartedMicrotime(),
                            'elapse' => $profile->getElapsedSecs(),
                        ];
                        dump($profileInfo);
                    }
                }
            }

        }

        return $response;
    }
}