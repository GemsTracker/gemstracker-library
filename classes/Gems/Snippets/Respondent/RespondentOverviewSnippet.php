<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2017 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.2
 */
class RespondentOverviewSnippet extends \Gems_Snippets_ModelTableSnippetAbstract {

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    //public $browse = true;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;
    public $showMenu = false;
    
    public $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_ROWS;

    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter = array(
        'gto_completion_time IS NOT NULL',
        'grc_success' => 1
    );

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    public $extraSort = array('gto_completion_time' => SORT_DESC);

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;
    
    public $menuActionController = array('track');
    public $menuShowActions = array('answer');

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems_Tracker_Respondent
     */
    protected $respondent;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function afterRegistry() {
        parent::afterRegistry();
        if (!($this->tracker instanceof \Gems_Tracker)) {
            $this->tracker = $this->loader->getTracker();
        }
    }
    
    public function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model) {
        
        parent::addBrowseTableColumns($bridge, $model);
        
        $showMenuItems = $this->getShowMenuItems();

        foreach ($showMenuItems as $menuItem) {
            $link = $menuItem->toActionLinkLower($this->request, $bridge);
            $link->target = 'inline';
            $bridge->addItemLink($link);
        }
    }

    public function getHtmlOutput(\Zend_View_Abstract $view) {
        // Make sure we can use jQuery
        \MUtil_JQuery::enableView($view);
                
        $view->jQuery()->addOnLoad('        
        // Inline answer loading for click on table
        $("tr[onclick^=\'javascript:location.href=\']").each(function(e){
            regex = /[\'"].*[\'"]/
            hrefvalue = $(this).attr(\'onclick\').match(regex)[0];
            href = hrefvalue.slice(1, -1);
            this.attributes.onclick.nodeValue = "loadInline(\'" + href + "\')";
        });
    ');

        $br              = \MUtil_Html::create('br');
        $this->columns[] = array('gto_completion_time');
        $this->columns[] = array('gsu_survey_name');
        $this->columns[] = array('forgroup');
        $this->columns[] = array('gto_id_token');
        
        $html = parent::getHtmlOutput($view);
        if($roundDescription = $this->request->getParam('gto_round_description')) {
            $html->caption($roundDescription);
        }
        
        return $html;
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel() {
        if (!$this->model instanceof \Gems_Tracker_Model_StandardTokenModel) {
            $model = $this->loader->getTracker()->getTokenModel();
            $model->set('gto_id_token', 'label', $this->_('Summary'), 'formatFunction', array($this, 'getData'));
            $model->set('gsu_survey_name', 'label', $this->_('Survey'));
            $model->set('forgroup', 'label', $this->_('Filler'));
            $model->setKeys(array('gr2o_patient_nr', 'gto_id_organization'));

            $this->model = $model;
        }

        return $this->model;
    }

    public function getData($tokenId) {
        try {
            $token = $this->tracker->getToken($tokenId);
            $responses = $token->getRawAnswers();
            $scores = array();
            $questions = $token->getSurvey()->getQuestionList($this->loader->getCurrentUser()->getLocale());
            foreach($responses as $key=>$value) {
                if (strtoupper(substr($key,0,5)) == 'SCORE') {
                    if (empty($value)) {
                        $value = $this->_('n/a');
                    }
                    if (!array_key_exists($key, $questions)) {
                        $scores[$key] = $value;
                    } else {                        
                        $scores[$questions[$key]] = $value;
                    }
                }

            }
            if (!empty($scores)) {
                $repeater = new \MUtil_Lazy_RepeatableByKeyValue($scores);
                $div      = \MUtil_Html::create('div')->setRepeater($repeater)->setAttrib('class', 'row');
                $div->div($repeater->key, array('class' => 'col-md-6'))->setOnEmpty(\MUtil_Html::raw('empty'));
                $div->div($repeater->value, array('class' => 'col-md-6', 'renderWithoutContent'=>false));
                return $div;
            } else {
                return \MUtil_Html::create('div', array('class'=>'row'))->div($this->_('No summary available'), array('class'=>'col-md-12'));
            }
        } catch (\Exception $exc) {
            return null;
        }
    }
    
    /**
     * Fix for forward slash in round description 
     * 
     * The round description can contain a / that is interpreted incorrect, so 
     * in TrafficLightTokenSnippet we encode it to the html entity first. This method does the reverse.
     * 
     * @see \Gems_Snippets_Respondent_TrafficLightTokenSnippet
     * @param \MUtil_Model_ModelAbstract $model
     */
    public function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        // 
        $roundDecription  = $this->request->getParam('gto_round_description');
        if (!is_null($roundDecription)) {
            $roundDecription = html_entity_decode(urldecode($roundDecription));
            $this->request->setParam('gto_round_description', $roundDecription);
        }
        parent::processFilterAndSort($model);
    }

}
