<?php

declare(strict_types=1);

namespace Gems\Handlers\Setup\Messenger;

use Carbon\Carbon;
use Gems\Handlers\GemsHandler;
use Gems\Model\Setup\MessageListModel;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Messenger\MessageListSearchSnippet;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\Browse\BrowseTableAction;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class MessageListHandler extends GemsHandler
{
    public static $actions = [
        'index' => BrowseSearchAction::class,
        'show'  => ShowAction::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        private readonly MessageListModel $messengerListModel,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        if ($action->isDetailed()) {
            $this->messengerListModel->applyDetailSettings();
        }

        return $this->messengerListModel;
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        if ($action instanceof BrowseTableAction) {
            $action->trackUsage = false;
            $action->addToSort(['available_at' => SORT_DESC]);
        }
        if ($action instanceof BrowseSearchAction) {
            $action->setStartSnippets([
                ContentTitleSnippet::class,
                MessageListSearchSnippet::class,
            ]);

            $carbon = new Carbon();
            $oneMonthAgo = $carbon->subMonth();
            $startOfDay = $oneMonthAgo->startOfDay();
            $this->defaultSearchData['datefrom'] = $startOfDay;
            $this->defaultSearchData['dateused'] = 'available_at';
        }

        parent::prepareAction($action);
    }
}