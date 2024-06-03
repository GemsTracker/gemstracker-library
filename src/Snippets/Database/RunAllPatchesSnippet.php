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

class RunAllPatchesSnippet extends ModelSnippetAbstract
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

        $patchItems = $model->load(['status' => ['new', 'error']]);

        if (!$patchItems) {
            $this->statusMessenger->addInfo($this->translate->_('No patch to execute'));
            return;
        }

        // Sort the patch items.
        usort($patchItems, function($a, $b) {
            return $a['order'] - $b['order'];
        });

        foreach($patchItems as $patchItem) {
            try {
                $this->patchRepository->runPatch($patchItem);
                $this->statusMessenger->addSuccess(
                    sprintf($this->translate->_('Patch %s has been succesfully executed'), $patchItem['name'])
                );
            } catch (\Exception $e) {
                $this->statusMessenger->addError(
                    sprintf($this->translate->_('Error executing patch %s. %s'), $patchItem['name'], $e->getMessage())
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