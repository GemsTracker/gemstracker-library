<?php

/**
 *
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers;

use DateTimeImmutable;
use Gems\Model\LogModel;
use Gems\Model\Type\GemsDateTimeType;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Log\LogSearchSnippet;
use Gems\Snippets\Log\LogShowSnippet;
use Gems\Snippets\Log\LogTableSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Browse\FastBrowseSearchAction;
use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\Form\CreateAction;
use Gems\SnippetsActions\Form\EditAction;
use Gems\SnippetsActions\Show\ShowAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\Browse\BrowseTableAction;
use Zalt\SnippetsActions\Delete\DeleteAction;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Show the action log
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class LogHandler extends BrowseChangeHandler
{
    /**
     * Defined in Gems\Handlers\BrowseChangeHandler.
     *
     * @inheritdoc
     */
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => FastBrowseSearchAction::class, // Override to disable totals
        'create'     => CreateAction::class,
        'export'     => ExportAction::class,
        'edit'       => EditAction::class,
        'delete'     => DeleteAction::class,
        'show'       => ShowAction::class,
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = [
        LogTableSnippet::class,
    ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = [
        ContentTitleSnippet::class,
        LogSearchSnippet::class,
    ];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        LogShowSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected LogModel $logModel,
        protected PeriodSelectRepository $periodSelectRepository,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        if ($action->isDetailed()) {
            $this->logModel->applyDetailSettings();
        } else {
            $this->logModel->applyBrowseSettings();
        }

        return $this->logModel;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Logging');
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults(): array
    {
        if (! $this->defaultSearchData) {
            $from = new DateTimeImmutable('-14 days');
            $until = new DateTimeImmutable('+1 day');

            $this->defaultSearchData = [
                'datefrom'         => $from,
                'dateuntil'        => $until,
                'dateused'         => 'gla_created',
            ];
        }

        return parent::getSearchDefaults();
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param bool $useSession Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useSession = false): array
    {
        $filter = parent::getSearchFilter($useSession);

        $type  = new GemsDateTimeType($this->translate);
        $where = $this->periodSelectRepository->createPeriodFilter($filter, $type->dateFormat, $type->storageFormat, $this->getSearchDefaults());
        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('log', 'log', $count);
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if ($action instanceof BrowseSearchAction) {
            $action->setStartSnippets($this->indexStartSnippets);
        } elseif ($action instanceof BrowseTableAction) {
            $action->setSnippets($this->autofilterSnippets);
        } elseif ($action instanceof ShowAction) {
            $action->setSnippets($this->showSnippets);
        }
    }
}
