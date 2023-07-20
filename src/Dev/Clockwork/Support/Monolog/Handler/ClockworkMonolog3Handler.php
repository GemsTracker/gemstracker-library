<?php

namespace Gems\Dev\Clockwork\Support\Monolog\Handler;

use Clockwork\Request\Log as ClockworkLog;
use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;

class ClockworkMonolog3Handler extends AbstractProcessingHandler
{
    public function __construct(private readonly ClockworkLog $clockworkLog)
    {
        parent::__construct();
    }

    protected function write(LogRecord $record): void
    {
        $this->clockworkLog->log($record->level, $record->message);
    }
}