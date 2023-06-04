<?php

namespace Gems\Batch;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\Task\TaskRunnerBatch;
use Symfony\Contracts\Translation\TranslatorInterface;

class BatchRunnerLoader
{
    public function __construct(private TranslatorInterface $translate, private LayoutRenderer $layoutRenderer)
    {}

    public function getBatchRunner(TaskRunnerBatch $batch, ?LayoutSettings $layoutSettings = null): BatchRunner
    {
        if ($layoutSettings === null) {
            $layoutSettings = new LayoutSettings();
        }
        return new BatchRunner($batch, $this->translate, $this->layoutRenderer, $layoutSettings);
    }
}