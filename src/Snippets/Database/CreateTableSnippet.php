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

class CreateTableSnippet extends SnippetAbstract
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

        $params = $this->requestInfo->getRequestMatchedParams();
        if (!isset($params['name'])) {
            $this->statusMessenger->addError($this->translator->_('No valid name'));
            return;
        }

        $tableItem = $model->loadFirst(['name' => $params['name']]);

        if (!$tableItem) {
            $this->statusMessenger->addError(sprintf($this->translator->_('Table %s not found'), $params['name']));
            return;
        }

        try {
            $this->tableRepository->createTable($tableItem);
            $this->statusMessenger->addSuccess(sprintf($this->translator->_('Table %s has been succesfully created'), $params['name']));
        } catch(\Exception $e) {
            $this->statusMessenger->addError(sprintf($this->translator->_('Error creating table %s. %s'), $params['name'], $e->getMessage()));
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