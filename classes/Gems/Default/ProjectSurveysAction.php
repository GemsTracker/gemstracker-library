<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_ProjectSurveysAction extends \Gems_Controller_ModelSnippetActionAbstract
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
    protected $autofilterParameters = array(
        'extraFilter' => array(
            'gsu_surveyor_active' => 1,
            'gsu_active'          => 1,
            ),
        'extraSort' => array(
            'gsu_survey_name' => SORT_ASC,
            ),
        );

    /**
     *
     * @var \Gems_User_Organization
     */
    public $currentOrganization;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

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
    protected $showParameters = array('surveyId' => '_getIdParam');

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'ModelItemTableSnippetGeneric',
        'Survey\\SurveyQuestionsSnippet'
        );

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $yesNo = $this->util->getTranslated()->getYesNo();

        $model = new \Gems_Model_JoinModel('surveys', 'gems__surveys');
        $model->addTable('gems__groups',  array('gsu_id_primary_group' => 'ggp_id_group'));

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
                    'formatFunction', array(__CLASS__, 'formatDescription')
                    );
            $model->set('gsu_active',             'label', sprintf($this->_('Active in %s'), $this->project->getName()),
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
     * @return \MUtil_Html_Raw
     */
    public static function formatDescription($value)
    {
        return \MUtil_Html::raw(strip_tags($value));
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
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
    public function getSearchDefaults()
    {
        if (! $this->defaultSearchData) {
            $orgId = $this->currentOrganization->getId();
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
    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }
}
