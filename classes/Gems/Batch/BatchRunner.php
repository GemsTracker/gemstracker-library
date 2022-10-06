<?php

namespace Gems\Batch;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use MUtil\Batch\BatchAbstract;
use MUtil\Batch\Progress;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\TranslateableTrait;

class BatchRunner
{
    use TranslateableTrait;

    protected null|string|array $jobInfo = null;

    protected null|string $title = null;

    public function __construct(protected BatchAbstract $batch,
        TranslatorInterface $translate,
        protected LayoutRenderer $layoutRenderer,
        protected LayoutSettings $layoutSettings,
    )
    {
        $this->translate = $translate;
    }

    public function getResponse(ServerRequestInterface $request): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();

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
            if ($this->batch->run($queryParams)) {
                return $this->reportProgress($this->batch->getProgress(), $this->batch->getMessages());
            }
        }

        if (isset($queryParams[$this->batch->progressParameterName]) && $queryParams[$this->batch->progressParameterName] == $this->batch->progressParameterRestartValue) {
            $this->batch->reset();
            return $this->reportProgress($this->batch->getProgress(), $this->batch->getMessages());
        }

        $this->layoutSettings->addVue();
        $data = [
            'tag' => 'batch-runner',
            'attributes' => [
                'title' => $this->getTitle(),
            ],
        ];

        return new HtmlResponse($this->layoutRenderer->render($this->layoutSettings, $request, $data));
    }

    protected function getTitle(): ?string
    {
        return $this->title;
    }

    public function reportProgress(Progress $progress, ?array $messages): JsonResponse
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

    /**
     * @param array|string|null $jobInfo
     */
    public function setJobInfo(array|string|null $jobInfo): void
    {
        $this->jobInfo = $jobInfo;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}