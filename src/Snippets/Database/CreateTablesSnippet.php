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

class CreateTablesSnippet extends ModelSnippetAbstract
{
    use DataReaderGenericModelTrait;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly TableRepository $tableRepository,
        protected readonly StatusMessengerInterface $statusMessenger,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    protected function createTable(): void
    {
        $model = $this->getModel();

        $tableItems = $model->load(['status' => ['new', 'error']]);

        if (!$tableItems) {
            $this->statusMessenger->addInfo($this->translate->_('No tables to execute'));
            return;
        }

        foreach($tableItems as $tableItem) {
            try {
                $this->tableRepository->createTable($tableItem);
                $this->statusMessenger->addSuccess(
                    sprintf($this->translate->_('Table %s has been succesfully created'), $tableItem['name'])
                );
            } catch (\Exception $e) {
                $this->statusMessenger->addError(
                    sprintf($this->translate->_('Error creating table %s. %s'), $tableItem['name'], $e->getMessage())
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