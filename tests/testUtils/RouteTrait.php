<?php

namespace GemsTest\testUtils;

use Gems\InitFunctions;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;

trait RouteTrait
{
    protected function initRoutes(): void
    {
        $app = $this->container->get(Application::class);
        $factory = $this->container->get(MiddlewareFactory::class);
        InitFunctions::routes($app, $factory, $this->container);
    }
}