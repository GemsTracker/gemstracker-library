<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\PatchRepository;
use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\DataReaderGenericModelTrait;
use Zalt\Snippets\ModelSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class RunPatchSnippet extends ModelSnippetAbstract
{
    use DataReaderGenericModelTrait;
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly PatchRepository $patchRepository,
        protected readonly StatusMessengerInterface $statusMessenger,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createTable(): void
    {
        $model = $this->getModel();

        $params = $this->requestInfo->getRequestMatchedParams();
        if (!isset($params['name'])) {
            $this->statusMessenger->addError($this->translate->_('No valid name'));
            return;
        }

        $patchItem = $model->loadFirst(['name' => $params['name']]);

        if (!$patchItem) {
            $this->statusMessenger->addError(sprintf($this->translate->_('Patch %s not found'), $params['name']));
            return;
        }

        try {
            $this->patchRepository->runPatch($patchItem);
            $this->statusMessenger->addSuccess(sprintf($this->translate->_('Patch %s successfully executed'), $params['name']));
        } catch(\Exception $e) {
            $this->statusMessenger->addError(sprintf($this->translate->_('Error executing patch %s. %s'), $params['name'], $e->getMessage()));
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