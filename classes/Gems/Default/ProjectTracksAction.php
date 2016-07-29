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
class Gems_Default_ProjectTracksAction extends \Gems_Controller_ModelSnippetActionAbstract
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
            'gtr_active' => 1,
            -2 => '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE) AND gtr_date_start <= CURRENT_DATE'
            ),
        'extraSort' => array(
            'gtr_track_name' => SORT_ASC,
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
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array(
        'showHeader' => true,
        'trackId'    => '_getIdParam',
        'trackData'  => 'getTrackData',
        );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic\\ContentTitleSnippet',
        'Tracker\\TrackUsageTextDetailsSnippet',
        'ModelItemTableSnippetGeneric',
        'Tracker\\TrackSurveyOverviewSnippet',
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
        $translated = $this->util->getTranslated();

        $model = new \MUtil_Model_TableModel('gems__tracks');

        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gtr_survey_rounds', 'label', $this->_('Survey #'));
        $model->set('gtr_date_start',    'label', $this->_('From'),
                'dateFormat', $translated->formatDate,
                'tdClass', 'date'
                );
        $model->set('gtr_date_until',    'label', $this->_('Until'),
                'dateFormat', $translated->formatDateForever,
                'tdClass', 'date'
                );

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Active tracks');
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
            $this->defaultSearchData[-1] = "gtr_organizations LIKE '%|$orgId|%'";
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
        return $this->plural('track', 'tracks', $count);
    }

    /**
     *
     * @return array
     */
    public function getTrackData()
    {
        return $this->getModel()->loadFirst();
    }

    /**
     * Show the questions in a survey
     */
    public function questionsAction()
    {
        $this->addSnippet('Survey\\SurveyQuestionsSnippet',
                'menu', $this->menu,
                'surveyId', $this->_getParam(\Gems_Model::SURVEY_ID),
                'trackId', $this->_getIdParam()
                );
    }
}
