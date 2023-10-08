<?php

namespace Gems\Batch;

use Gems\Layout\LayoutSettings;
use Gems\Task\TaskRunnerBatch;
use Zalt\Base\TranslatorInterface;

class BatchRunnerLoader
{
    public function __construct(
        private TranslatorInterface $translator,
        private LayoutSettings $layoutSettings,
        private readonly array $config,
        )
    {}

    public function getBatchRunner(TaskRunnerBatch $batch): BatchRunner
    {
        return new BatchRunner($batch, $this->translator, $this->layoutSettings, $this->config);
    }
}