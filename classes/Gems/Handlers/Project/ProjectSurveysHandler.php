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
use Gems\Snippets\ModelItemTableSnippet;
use Gems\Snippets\Survey\SurveyQuestionsSnippet;
use Gems\Util\Translated;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        'extraFilter' => [
             'gsu_surveyor_active' => 1,
             'gsu_active'          => 1,
        ],
        'extraSort' => [
            'gsu_survey_name' => SORT_ASC,
        ],
    ];

    /**
     * @var array
     */
    public array $config;

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
        ModelItemTableSnippet::class,
        CurrentButtonRowSnippet::class,
        SurveyQuestionsSnippet::class,
    ];

    public function __construct(
        SnippetResponderInterface $responder, 
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct($responder, $translate);
        
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
    protected function createModel($detailed, $action): ModelAbstract
    {
        $yesNo = $this->translatedUtil->getYesNo();

        $model = new \Gems\Model\JoinModel('surveys', 'gems__surveys');
        $model->addTable('gems__groups',  ['gsu_id_primary_group' => 'ggp_id_group']);

        $model->addColumn(
                "(SELECT COUNT(DISTINCT gro_id_track)
                    FROM gems__tracks INNER JOIN gems__rounds ON gtr_id_track = gro_id_track
                    WHERE gro_id_survey = gsu_id_survey)",
                'track_count'
                );

        $model->resetOrder();

        $model->set('gsu_survey_name', 'label', $this->_('Survey'));

        if ($detailed) {
            $model->set('gsu_survey_description', 'label', $this->_('Description'),
                    'formatFunction', [__CLASS__, 'formatDescription']
                    );
            $model->set('gsu_active',             'label', sprintf($this->_('Active in %s'), $this->config['app']['name']),
                    'elementClass', 'Checkbox',
                    'multiOptions', $yesNo
                    );
        }

        $model->set('ggp_name',        'label', $this->_('By'));
        $model->set('track_count',     'label', $this->_('Usage'),
                'description', $this->_('How many track definitions use this survey?'));
        $model->set('gsu_insertable',  'label', $this->_('Insertable'),
                'description', $this->_('Can this survey be manually inserted into a track?'),
                'multiOptions', $yesNo
                );

        if ($detailed) {
            $model->set('gsu_duration',         'label', $this->_('Duration description'),
                    'description', $this->_('Text to inform the respondent, e.g. "20 seconds" or "1 minute".')
                    );
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

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
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
     * @return $string
     */
    public function getTopic($count = 1): string
    {
        return $this->plural('survey', 'surveys', $count);
    }
}
