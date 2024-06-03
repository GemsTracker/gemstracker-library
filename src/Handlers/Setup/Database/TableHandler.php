<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Db\Migration\MigrationModelFactory;
use Gems\Db\Migration\MigrationRepositoryAbstract;
use Gems\Db\Migration\TableRepository;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsHandler\CreateModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class TableHandler extends MigrationHandlerAbstract
{
    use CreateModelHandlerTrait;

    public static $actions = [
        'index' => BrowseSearchAction::class,
        'new' => BrowseNewSearchAction::class,
        'create' => CreateTableAction::class,
        'create-all' => CreateAllTablesAction::class,
        'show' => ShowAction::class,
    ];

    public static array $parameters = [
      'id' => '[a-zA-Z0-9-_]+',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        MigrationModelFactory $migrationModelFactory,
        protected readonly array $config,
        protected readonly TableRepository $tableRepository,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate, $cache, $migrationModelFactory);
    }

    protected function getRepository(): MigrationRepositoryAbstract
    {
        return $this->tableRepository;
    }

    public function getTopic(int $count = 1) : string
    {
        return $this->plural('table', 'tables', $count);
    }
}