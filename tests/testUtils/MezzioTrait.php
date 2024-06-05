<?php

namespace GemsTest\testUtils;

use Mezzio\Application;

trait MezzioTrait
{
    protected Application $app;
    public function initApp(): void
    {
        $this->app = $this->container->get(Application::class);
    }
}