<?php

namespace Gems\Batch;

use Gems\Layout\LayoutSettings;
use Gems\Task\TaskRunnerBatch;
use Symfony\Contracts\Translation\TranslatorInterface;

class BatchRunnerLoader
{
    public function __construct(
        private TranslatorInterface $translate,
        private LayoutSettings $layoutSettings,
        private readonly array $config,
        )
    {}

    public function getBatchRunner(TaskRunnerBatch $batch): BatchRunner
    {
        return new BatchRunner($batch, $this->translate, $this->layoutSettings, $this->config);
    }
}