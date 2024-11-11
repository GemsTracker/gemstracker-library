<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\SeedRepository;
use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\DataReaderGenericModelTrait;
use Zalt\Snippets\ModelSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class RunSeedSnippet extends ModelSnippetAbstract
{
    use DataReaderGenericModelTrait;
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly SeedRepository $seedRepository,
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

        $seedItem = $model->loadFirst(['name' => $params['name']]);

        if (!$seedItem) {
            $this->statusMessenger->addError(sprintf($this->translate->_('Seed %s not found'), $params['name']));
            return;
        }

        try {
            $this->seedRepository->runSeed($seedItem);
            $this->statusMessenger->addSuccess(sprintf($this->translate->_('Seed %s successfully executed'), $params['name']));
        } catch(\Exception $e) {
            $this->statusMessenger->addError(sprintf($this->translate->_('Error executing seed %s. %s'), $params['name'], $e->getMessage()));
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