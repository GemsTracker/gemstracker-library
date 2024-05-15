<?php

namespace Gems\Handlers\Export;

use Gems\Export\AnswerModelFactory;
use Gems\Handlers\BrowseChangeHandler;
use Gems\Model\PlaceholderModel;
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

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        protected readonly AnswerModelFactory $answerModelFactory,
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

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);
        if ($action instanceof BrowseSearchAction) {
            $action->setStartSnippets([
                ContentTitleSnippet::class,
                SurveyExportSearchFormSnippet::class,
            ]);
        }

        $searchFilter =  $this->getSearchFilter();
        $searchData = $this->getSearchData();
        if ($action instanceof BrowseFilteredAction && $searchFilter && isset($searchFilter['gto_id_survey'])) {
            $action->model = $this->answerModelFactory->getModel($searchFilter, $searchData);
        }
    }
}