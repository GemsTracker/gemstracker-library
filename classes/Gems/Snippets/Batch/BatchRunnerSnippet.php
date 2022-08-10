<?php

namespace Gems\Snippets\Batch;

use Laminas\Diactoros\Response\JsonResponse;
use MUtil\Batch\BatchAbstract;
use MUtil\Batch\Progress;
use MUtil\Request\RequestInfo;
use MUtil\Snippets\SnippetAbstract;

class BatchRunnerSnippet extends SnippetAbstract
{
    /**
     * @var BatchAbstract
     */
    protected $batch;

    /**
     * @var RequestInfo
     */
    protected $requestInfo;

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->batch->isFinished()) {
            return $this->showFinished();
        }
        if ($this->batch->count()) {
            return $this->showStart();
        }

        return null;
        // Nothing to do
    }

    public function getResponse()
    {
        if ($this->batch->run($this->requestInfo->getRequestQueryParams())) {
            return $this->reportProgress($this->batch->getProgress(), $this->batch->getLastMessage());
        }
    }

    public function reportProgress(Progress $progress, ?string $message)
    {
        $data = [
            'percent' => $progress->getPercent(),
            'text' => $message,
            'timeElapsed' => $progress->getEstimated(),
            'timeRemaining' => $progress->getRemaining(),
        ];

        return new JsonResponse($data);
    }

    protected function showFinished()
    {

    }

    protected function showStart()
    {

    }

}
