<?php

namespace Gems\Snippets\Batch;

use Laminas\Diactoros\Response\JsonResponse;
use MUtil\Batch\BatchAbstract;
use MUtil\Batch\Progress;
use MUtil\Request\RequestInfo;
use MUtil\Snippets\SnippetAbstract;
use Psr\Http\Message\ResponseInterface;

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

    protected null|string|array $jobInfo = null;

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        return null;
        // Nothing to do
    }

    public function getResponse(): ?ResponseInterface
    {
        $queryParams = $this->requestInfo->getRequestQueryParams();

        if (isset($queryParams[$this->batch->progressParameterName]) && $queryParams[$this->batch->progressParameterName] == 'init') {
            $progress = $this->batch->getProgress();
            $data = [
                'count' => $progress->getCount(),
                'translations' => [
                    'cancel' => $this->_('Cancel'),
                    'restart' => $this->_('Restart'),
                    'start' => sprintf($this->_('Start %s jobs'), $progress->getCount()),
                ],
                'info' => $this->jobInfo,
            ];
            return new JsonResponse($data);
        }
        if (isset($queryParams[$this->batch->progressParameterName]) && $queryParams[$this->batch->progressParameterName] == $this->batch->progressParameterRunValue) {
            if ($this->batch->isFinished()) {
                $this->batch->reset();
                return $this->reportProgress($this->batch->getProgress(), $this->batch->getMessages());
            }
            if ($this->batch->run($this->requestInfo->getRequestQueryParams())) {
                return $this->reportProgress($this->batch->getProgress(), $this->batch->getMessages());
            }
        }

        if (isset($queryParams[$this->batch->progressParameterName]) && $queryParams[$this->batch->progressParameterName] == $this->batch->progressParameterRestartValue) {
            $this->batch->reset();
            return $this->reportProgress($this->batch->getProgress(), $this->batch->getMessages());
        }

        return null;
    }

    public function reportProgress(Progress $progress, ?array $messages)
    {
        $data = [
            'count' => $progress->getCount(),
            'percent' => $progress->getPercent(),
            'messages' => $messages,
            'timeElapsed' => $progress->getEstimated(),
            'timeRemaining' => $progress->getRemaining(),
        ];

        return new JsonResponse($data);
    }
}
