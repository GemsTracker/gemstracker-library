<?php

namespace Gems\Log;

use \DateTimeZone;
use \WeakMap;
use Monolog\Logger;

class ErrorLogger extends Logger
{
    protected WeakMap $fiberLogDepth;

    public function __construct(
        string $name = 'gems',
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        parent::__construct($name, $handlers, $processors, $timezone);
        $this->name = $name;
        $this->setHandlers($handlers);
        $this->processors = $processors;
        $this->timezone = $timezone ?? new DateTimeZone(date_default_timezone_get());
        $this->fiberLogDepth = new WeakMap();
    }
}