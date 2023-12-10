<?php

namespace Gems\Snippets\Communication;

use Gems\Db\ResultFetcher;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\CommJobRepository;
use Gems\Snippets\Generic\PrevNextButtonRowSnippetAbstract;
use MUtil\Model;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class CommJobButtonRowSnippet extends PrevNextButtonRowSnippetAbstract
{

    protected ?array $commJobData = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuHelper,
        protected CommJobRepository $commJobRepository,
        protected ResultFetcher $resultFetcher,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuHelper);
    }

    protected function getCommJobData(): ?array
    {
        if ($this->commJobData === null) {
            $requestParams = $this->requestInfo->getRequestMatchedParams();
            $this->commJobData = $this->commJobRepository->getJob($requestParams[Model::REQUEST_ID]);
        }
        return $this->commJobData;
    }

    protected function getNextUrl(): ?string
    {
        $currentCommJobData = $this->getCommJobData();
        $select = $this->resultFetcher->getSelect('gems__comm_jobs');
        $select->columns(['gcj_id_job'])
            ->order(['gcj_id_order'])
            ->where->greaterThan('gcj_id_order', $currentCommJobData['gcj_id_order']);

        $next = $this->resultFetcher->fetchOne($select);
        if (null === $next) {
            return null;
        }

        $route = $this->menuHelper->getCurrentRoute();
        return $this->menuHelper->getRouteUrl($route, [MetaModelInterface::REQUEST_ID => $next]);
    }

    protected function getPreviousUrl(): ?string
    {
        $currentCommJobData = $this->getCommJobData();
        $select = $this->resultFetcher->getSelect('gems__comm_jobs');
        $select->columns(['gcj_id_job'])
            ->order(['gcj_id_order'])
            ->where->lessThan('gcj_id_order', $currentCommJobData['gcj_id_order']);

        $prev = $this->resultFetcher->fetchOne($select);
        if (null === $prev) {
            return null;
        }

        $route = $this->menuHelper->getCurrentRoute();
        return $this->menuHelper->getRouteUrl($route, [MetaModelInterface::REQUEST_ID => $prev]);
    }
}