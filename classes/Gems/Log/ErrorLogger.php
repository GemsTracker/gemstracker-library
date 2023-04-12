<?php

namespace Gems\Log;

use \DateTimeZone;
use \WeakMap;
use Monolog\Logger;

class ErrorLogger extends Logger
{
    public function __construct(string $name = 'gems', array $handlers = [], array $processors = [], DateTimeZone|null $timezone = null)
    {
        $this->name = $name;
        $this->setHandlers($handlers);
        $this->processors = $processors;
        $this->timezone = $timezone ?? new DateTimeZone(date_default_timezone_get());
        $this->fiberLogDepth = new WeakMap();
    }
}