<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Project;

use Gems\Html;
use Gems\Legacy\CurrentUserRepository;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\Survey\SurveyQuestionsSnippet;
use Gems\SnippetsActions\Browse\BrowseSearchAction;
use Gems\SnippetsActions\Show\ShowAction;
use Gems\Tracker\Model\SurveyMaintenanceModel;
use Psr\Cache\CacheItemPoolInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class ProjectSurveysHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = [
        'columns' => 'getBrowseColumns',
        'extraFilter' => [
             'gsu_surveyor_active' => 1,
             'gsu_active'          => 1,
        ],
        'extraSort' => [
            'gsu_survey_name' => SORT_ASC,
        ],
    ];

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $showParameters = ['surveyId' => '_getIdParam'];

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected array $showSnippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CurrentButtonRowSnippet::class,
        SurveyQuestionsSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder, 
        TranslatorInterface $translate,
        CacheItemPoolInterface $cache,
        CurrentUserRepository $currentUserRepository,
        protected readonly SurveyMaintenanceModel $surveyMaintenanceModel,
    )
    {
        parent::__construct($responder, $translate, $cache);
        
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel($detailed, $action): DataReaderInterface
    {
        if ($detailed) {
            $actionClass = new ShowAction();
        } else {
            $actionClass = new BrowseSearchAction();
        }
        $this->surveyMaintenanceModel->applyAction($actionClass);

        $model = $this->surveyMaintenanceModel;
        if ($detailed) {
            $model->getMetaModel()->setCol(
                ['gsu_surveyor_id', 'gsu_surveyor_active', 'gsu_survey_pdf', 'gsu_beforeanswering_event', 
                    'gsu_completed_event', 'gsu_display_event', 'gsu_id_source', 'gsu_active', 'gsu_status', 
                    'gsu_survey_warnings', 'gsu_allow_export', 'gsu_code', 'gsu_export_code'], 
                ['label' => null]);
        } else {
            $model->getMetaModel()->set('track_count', 'label', $this->_('Usage'));
            $model->addColumn('COALESCE(gsu_external_description, gsu_survey_name)', 'used_external_description', 'gsu_external_description');
        }
        
        return $model;
    }

    /**
     * Strip all the tags, but keep the escaped characters
     *
     * @param string $value
     * @return \Zalt\Html\Raw
     */
    public static function formatDescription($value)
    {
        return Html::raw(strip_tags($value));
    }

    public function getBrowseColumns() : bool|array
    {
        $br = Html::br();
        return [
            10 => ['gsu_survey_name', $br, 'used_external_description', $br, 'gsu_survey_description'],
            20 => ['gsu_id_primary_group', $br, 'gsu_survey_languages'],
            30 => ['track_count', 'gsu_insertable']
        ];
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Active surveys');
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
            $orgId = $this->currentUser->getCurrentOrganizationId();
            $this->defaultSearchData[-1] = "((gsu_insertable = 1 AND gsu_insert_organizations LIKE '%|$orgId|%') OR
                EXISTS
                (SELECT gro_id_track FROM gems__tracks INNER JOIN gems__rounds ON gtr_id_track = gro_id_track
                    WHERE gro_id_survey = gsu_id_survey AND gtr_organizations LIKE '%|$orgId|%'
                    ))";
        }

        return parent::getSearchDefaults();
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('survey', 'surveys', $count);
    }
}
