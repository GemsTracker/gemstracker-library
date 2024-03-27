<?php

namespace Gems\Handlers\Setup;

use Gems\Handlers\GemsHandler;
use Gems\Model\Setup\QueueTransportMessageCountModel;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class QueueMessageCountHandler extends GemsHandler
{
    public static $actions = [
        'index' => BrowseSearchAction::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly QueueTransportMessageCountModel $messageCountModel,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        return $this->messageCountModel;
    }
}