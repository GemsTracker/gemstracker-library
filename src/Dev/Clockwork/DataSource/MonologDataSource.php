<?php

namespace Gems\Dev\Clockwork\DataSource;

use Clockwork\Request\Log;
use Gems\Dev\Clockwork\Support\Monolog\Handler\ClockworkMonolog3Handler;
use Monolog\Logger as Monolog;

class MonologDataSource extends \Clockwork\DataSource\MonologDataSource
{
    public function __construct(Monolog $monolog)
    {
        $this->log = new Log;

        $monolog->pushHandler(new ClockworkMonolog3Handler($this->log));
    }
}