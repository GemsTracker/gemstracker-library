<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\PatchRepository;
use Gems\Menu\MenuSnippetHelper;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class RunPatchSnippet extends SnippetAbstract
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
        if (!isset($params['name'])) {
            $this->statusMessenger->addError($this->translator->_('No valid name'));
            return;
        }

        $patchItem = $model->loadFirst(['name' => $params['name']]);

        if (!$patchItem) {
            $this->statusMessenger->addError(sprintf($this->translator->_('Patch %s not found'), $params['name']));
            return;
        }

        try {
            $this->patchRepository->runPatch($patchItem);
            $this->statusMessenger->addSuccess(sprintf($this->translator->_('Patch %s successfully executed'), $params['name']));
        } catch(\Exception $e) {
            $this->statusMessenger->addError(sprintf($this->translator->_('Error executing patch %s. %s'), $params['name'], $e->getMessage()));
        }
    }

    public function getRedirectRoute(): ?string
    {
        return $this->menuSnippetHelper->getRelatedRouteUrl('show');
    }

    public function hasHtmlOutput(): bool
    {
        $this->createTable();

        return false;
    }
}