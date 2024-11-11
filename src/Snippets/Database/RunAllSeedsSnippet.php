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

class RunAllSeedsSnippet extends ModelSnippetAbstract
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

        $items = $model->load(['status' => ['new', 'error']]);

        if (!$items) {
            $this->statusMessenger->addInfo($this->translate->_('No seed to execute'));
            return;
        }

        foreach($items as $item) {
            try {
                $this->seedRepository->runSeed($item);
                $this->statusMessenger->addSuccess(
                    sprintf($this->translate->_('Seed %s has been successfully executed'), $item['name'])
                );
            } catch (\Exception $e) {
                $this->statusMessenger->addError(
                    sprintf($this->translate->_('Error executing seed %s. %s'), $item['name'], $e->getMessage())
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
