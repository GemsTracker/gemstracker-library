<?php

namespace Gems\Handlers\Setup\Database;

use Gems\Db\Migration\MigrationRepositoryAbstract;
use Gems\Db\Migration\SeedRepository;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsHandler\CreateModelHandlerTrait;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class SeedHandler extends MigrationHandlerAbstract
{
    use CreateModelHandlerTrait;

    public static $actions = [
        'index' => BrowseSearchAction::class,
        'new' => BrowseNewSearchAction::class,
        'run' => RunSeedAction::class,
        'run-all' => RunAllSeedsAction::class,
        'show' => ShowAction::class,
    ];

    public static array $parameters = [
      'name' => '[a-zA-Z0-9-_]+',
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        protected readonly array $config,
        protected readonly SeedRepository $seedRepository,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate);
    }

    protected function getRepository(): MigrationRepositoryAbstract
    {
        return $this->seedRepository;
    }

    public function getTopic(int $count = 1) : string
    {
        return $this->plural('table', 'tables', $count);
    }
}
