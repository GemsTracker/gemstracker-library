<?php

namespace Gems\Snippets\Database;

use Gems\Db\Migration\PatchRepository;
use Gems\Db\Migration\SeedRepository;
use Gems\Menu\MenuSnippetHelper;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Snippets\SnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

class RunSeedSnippet extends SnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly SeedRepository $seedRepository,
        protected readonly Translator $translator,
        protected readonly StatusMessengerInterface $statusMessenger,
    )
    {
        parent::__construct($snippetOptions, $requestInfo);
    }

    protected function createTable(): void
    {
        $model = $this->seedRepository->getModel();

        $params = $this->requestInfo->getRequestMatchedParams();
        if (!isset($params['name'])) {
            $this->statusMessenger->addError($this->translator->_('No valid name'));
            return;
        }

        $seedItem = $model->loadFirst(['name' => $params['name']]);

        if (!$seedItem) {
            $this->statusMessenger->addError(sprintf($this->translator->_('Seed %s not found'), $params['name']));
            return;
        }

        try {
            $this->seedRepository->runSeed($seedItem);
            $this->statusMessenger->addSuccess(sprintf($this->translator->_('Seed %s successfully executed'), $params['name']));
        } catch(\Exception $e) {
            $this->statusMessenger->addError(sprintf($this->translator->_('Error executing seed %s. %s'), $params['name'], $e->getMessage()));
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