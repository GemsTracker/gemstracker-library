<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\PatchRepository;
use Gems\Menu\MenuSnippetHelper;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class RunAllPatchesSnippet extends SnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly PatchRepository $patchRepository,
        protected readonly Translator $translator,
        protected readonly StatusMessengerInterface $statusMessenger,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
    }

    protected function createTable(): void
    {
        $model = $this->patchRepository->getModel();

        $params = $this->requestInfo->getRequestMatchedParams();

        $patchItems = $model->load(['status' => ['new', 'error']]);

        if (!$patchItems) {
            $this->statusMessenger->addInfo($this->translator->_('No patch to execute'));
            return;
        }

        foreach($patchItems as $patchItem) {
            try {
                $this->patchRepository->runPatch($patchItem);
                $this->statusMessenger->addSuccess(
                    sprintf($this->translator->_('Patch %s has been succesfully executed'), $params['name'])
                );
            } catch (\Exception $e) {
                $this->statusMessenger->addError(
                    sprintf($this->translator->_('Error executing patch %s. %s'), $params['name'], $e->getMessage())
                );
            }
        }
    }

    public function getRedirectRoute(): ?string
    {
        return $this->menuSnippetHelper->getRelatedRouteUrl('index');
    }

    public function hasHtmlOutput(): bool
    {
        $this->createTable();

        return false;
    }
}