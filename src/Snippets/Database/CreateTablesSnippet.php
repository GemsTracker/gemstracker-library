<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\TableRepository;
use Gems\Menu\MenuSnippetHelper;
use MUtil\Translate\Translator;
use PHPUnit\TextUI\XmlConfiguration\MigrationException;
use Zalt\Base\RequestInfo;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class CreateTablesSnippet extends SnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly TableRepository $tableRepository,
        protected readonly Translator $translator,
        protected readonly StatusMessengerInterface $statusMessenger,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
    }

    protected function createTable(): void
    {
        $model = $this->tableRepository->getModel();

        $tableItems = $model->load(['status' => ['new', 'error']]);

        if (!$tableItems) {
            $this->statusMessenger->addInfo($this->translator->_('No tables to execute'));
            return;
        }

        foreach($tableItems as $tableItem) {
            try {
                $this->tableRepository->createTable($tableItem);
                $this->statusMessenger->addSuccess(
                    sprintf($this->translator->_('Table %s has been succesfully created'), $tableItem['name'])
                );
            } catch (\Exception $e) {
                $this->statusMessenger->addError(
                    sprintf($this->translator->_('Error creating table %s. %s'), $tableItem['name'], $e->getMessage())
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