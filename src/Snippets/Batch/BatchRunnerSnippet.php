<?php

namespace Gems\Snippets\Batch;

use Gems\Batch\BatchRunner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class BatchRunnerSnippet extends SnippetAbstract
{
    protected BatchRunner $batchRunner;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected readonly ServerRequestInterface $request,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
    }


    public function getHtmlOutput()
    {
        return null;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->batchRunner->getResponse($this->request);
    }

    public function hasHtmlOutput(): bool
    {
        return false;
    }
}
