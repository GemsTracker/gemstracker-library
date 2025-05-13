<?php

namespace Gems\Handlers\Export;

use Gems\Export\Db\AnswerModelContainer;
use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\PlaceholderModel;
use Gems\Repository\PeriodSelectRepository;
use Gems\Snippets\Export\SurveyExportSearchFormSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Export\ExportAction;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModellerInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

class ExportSurveyHandler extends BrowseChangeHandler
{
    public static $actions = [
        'autofilter' => BrowseFilteredAction::class,
        'index'      => BrowseSearchAction::class,
        'export'     => ExportAction::class,
    ];

    protected bool $multi = false;

    protected array $exportStartSnippets = [
        ContentTitleSnippet::class,
        SurveyExportSearchFormSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly AnswerModelContainer $answerModelContainer,
        protected readonly PeriodSelectRepository $periodSelectRepository,
    ) {
        parent::__construct($responder, $metaModelLoader, $translate, $cache);
    }

    protected function getModel(SnippetActionInterface $action): MetaModellerInterface
    {
        $basicArray = [
            'gto_id_survey',
            'gto_id_track',
            'gto_round_description',
            'gto_id_organization',
            'gto_start_date',
            'gto_end_date',
            'gto_valid_from',
            'gto_valid_until'
        ];

        $placeholderModel = new PlaceholderModel($this->metaModelLoader, $this->translate, 'noSurvey', $basicArray);
        return $placeholderModel;
    }

    public function getSearchFilter(bool $useSessionReadonly = false): array
    {
        $filter = parent::getSearchFilter($useSessionReadonly);

        $filter['gco_code'] = 'consent given';
        $filter['grc_success'] = 1;
        $filter[] = 'gto_start_time IS NOT NULL';
        if (!isset($filter['incomplete']) || !$filter['incomplete']) {
            $filter[] = 'gto_completion_time IS NOT NULL';
        }

        if (isset($filter['dateused']) && $filter['dateused']) {
            $where = $this->periodSelectRepository->createPeriodFilter($filter);
            if ($where) {
                $filter[] = $where;
            }
        }

        if (isset($filter['ids'])) {
            $idStrings = $filter['ids'];

            $idArray = preg_split('/[\s,;]+/', $idStrings, -1, PREG_SPLIT_NO_EMPTY);

            if ($idArray) {
                $filter['gto_id_respondent'] = $idArray;
            }
        }

        return $filter;
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);
        if ($action instanceof BrowseSearchAction) {
            $action->setStartSnippets($this->exportStartSnippets);
        }

        $searchFilter =  $this->getSearchFilter();
        if ($action instanceof BrowseFilteredAction && $searchFilter && isset($searchFilter['gto_id_survey'])) {
            if ($this->multi) {
                if (!$action instanceof ExportAction) {
                    $action->setSnippets([]);
                }
            } else {
                $action->model = $this->answerModelContainer->get($searchFilter['gto_id_survey'], $searchFilter);
            }
        }

        if ($action instanceof ExportAction) {
            $action->modelContainer = $this->answerModelContainer;
            if ($searchFilter && isset($searchFilter['gto_id_survey'])) {
                $action->modelIdentifier = $searchFilter['gto_id_survey'];
            }
        }
    }
}