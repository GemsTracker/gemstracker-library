<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\TableRepository;
use Gems\Menu\MenuSnippetHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\DataReaderGenericModelTrait;
use Zalt\Snippets\ModelSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class CreateTableSnippet extends ModelSnippetAbstract
{
    use DataReaderGenericModelTrait;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly StatusMessengerInterface $statusMessenger,
        protected readonly TableRepository $tableRepository,
    ) {
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

        $tableItem = $model->loadFirst(['name' => $params['name']]);

        if (!$tableItem) {
            $this->statusMessenger->addError(sprintf($this->translate->_('Table %s not found'), $params['name']));
            return;
        }

        try {
            $this->tableRepository->createTable($tableItem);
            $this->statusMessenger->addSuccess(sprintf($this->translate->_('Table %s has been succesfully created'), $params['name']));
        } catch(\Exception $e) {
            $this->statusMessenger->addError(sprintf($this->translate->_('Error creating table %s. %s'), $params['name'], $e->getMessage()));
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