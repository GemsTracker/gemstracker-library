<?php

namespace GemsTest\testUtils;

use Gems\InitFunctions;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;

trait PipelineTrait
{
    protected function initPipeline(): void
    {
        $app = $this->container->get(Application::class);
        $factory = $this->container->get(MiddlewareFactory::class);
        InitFunctions::pipeline($app, $factory, $this->container);
    }
}