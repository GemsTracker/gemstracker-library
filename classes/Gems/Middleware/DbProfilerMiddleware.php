<?php

namespace Gems\Middleware;

use Gems\Db\Db;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Profiler\Profiler;
use Laminas\Db\Sql\Sql;
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
             * @var $dbAdapter Adapter
             */
            $dbAdapter = $this->container->get(Adapter::class);
            $profiler = $dbAdapter->getProfiler();
            if ($profiler instanceof Profiler) {
                $profiles = $profiler->getProfiles();
                foreach($profiles as $profile) {
                    dump($profile);
                }
            }
        }

        return $response;
    }
}