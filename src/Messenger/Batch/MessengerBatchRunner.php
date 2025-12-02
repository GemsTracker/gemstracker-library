<?php

declare(strict_types=1);

namespace Gems\Messenger\Batch;

use Gems\Layout\LayoutSettings;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\UrlArrayAttribute;

class MessengerBatchRunner
{
    protected bool $autoStart = false;

    protected string $formTitle = '';

    protected null|string|array $jobInfo = null;

    protected string $progressParameterName = 'progress';

    public function __construct(
        protected readonly Batch $batch,
        protected readonly object|array|string $batchMessageLoader,
        protected readonly MessengerBatchRepository $messengerBatchRepository,
        protected readonly TranslatorInterface $translator,
        protected readonly LayoutSettings $layoutSettings,
        protected readonly UrlHelper $urlHelper,
        array $config,
    )
    {
        $this->vueSettings = $config['vue'] ?? [];
    }

    protected function getInitResponse(): ResponseInterface
    {
        $data = [
            'translations' => [
                'cancel' => $this->translator->_('Cancel'),
                'restart' => $this->translator->_('Restart'),
                'start' => $this->translator->_('Start jobs'),
                'empty' => $this->translator->_('No tasks to do'),
            ],
            'info' => $this->jobInfo,
        ];
        return new JsonResponse($data);
    }

    protected function getJsAttributes(): array
    {
        $output[':autostart']  = $this->autoStart ? 'true' : 'false';
        $output['init-url']    = $this->getUrl('init');
        $output['run-url']     = $this->getUrl('run');
        $output['restart-url'] = $this->getUrl('restart');

        return $output;
    }

    protected function getStartResponse(): ResponseInterface
    {
        if ($this->batch->totalItems === null) {
            // Initialize with loader and dispatch!
            $this->loadItems();
        }

        return $this->reportProgress();
    }

    public function getResponse(ServerRequestInterface $request): ?ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams[$this->progressParameterName])) {
            $response = match($queryParams[$this->progressParameterName]) {
                'init' => $this->getInitResponse(),
                'start' => $this->getStartResponse(),
                'run' => $this->getRunResponse(),
                'restart' => $this->getRestartResponse(),
                default => null,
            };

            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }


        $this->layoutSettings->addVue($this->vueSettings);
        $this->layoutSettings->disableIdleCheck();
        $attributes = $this->getJsAttributes();
        $attributes['title'] = $this->getTitle();
        $this->layoutSettings->addLayoutParameter('tag', 'batch-runner')
            ->addLayoutParameter('attributes', $attributes);

        return null;
    }

    protected function getRestartResponse(): ResponseInterface
    {

        return $this->getRunResponse();
    }

    protected function getRunResponse(): ResponseInterface
    {
        return $this->reportProgress();
    }

    protected function getTitle(): ?string
    {
        return $this->formTitle;
    }

    protected function getUrl(string $progress): string
    {
        return UrlArrayAttribute::toUrlString([$this->urlHelper->getBasePath()] + [$this->progressParameterName => $progress]);
    }

    protected function loadItems(): void
    {
        if (is_callable($this->batchMessageLoader)) {
            call_user_func_array($this->batchMessageLoader, [$this->batch]);
            return;
        }
        if (is_array($this->batchMessageLoader)) {
            $this->batch->addMessages($this->batchMessageLoader);
            return;
        }
    }

    protected function reportProgress(): ResponseInterface
    {
        $data = [
            'count' => $this->batch->totalItems,
            'percent' => $this->batch->getPercent(),
            'messages' => $this->messengerBatchRepository->getBatchInfoList($this->batch->batchId),
            'finished' => $this->batch->totalItems === $this->batch->success,
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


}