<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Menno Dekker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TrafficLightTokenSnippet.php 2136 2014-09-29 14:58:20Z matijsdejong $
 */

/**
 * Show the track in a different way, ordered by round and group showing
 * traffic light color indicating the status of a token and uses inline
 * answer display.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Snippets_Respondent_TrafficLightTokenSnippet extends \Gems\Snippets\Token\RespondentTokenSnippet
{
    /**
     * Set a fixed model filter.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedFilter     = array(
        //'gto_valid_from <= NOW()'
    );

    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort       = array(
        'gr2t_start_date'         => SORT_DESC,
        'gto_id_respondent_track' => SORT_DESC,
        'gto_round_order'         => SORT_ASC,
        'gto_valid_from'          => SORT_ASC,
        'gto_round_description'   => SORT_ASC,
        'forgroup'                => SORT_ASC,
        'gto_round_order'         => SORT_ASC
    );

    protected $_completed       = 0;
    protected $_open            = 0;
    protected $_missed          = 0;
    protected $_future          = 0;
    protected $_completedTrack  = 0;
    protected $_openTrack       = 0;
    protected $_missedTrack     = 0;
    protected $_futureTrack     = 0;

    /**
     * The display format for the date
     *
     * @var string
     */
    protected $_dateFormat;

    /**
     * The display format for the date/time fields
     *
     * @var string
     */
    protected $_dateTimeFormat;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_overview;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_surveyAnswer;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_takeSurvey;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_tokenEdit;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_tokenCorrect;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_tokenPreview;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_tokenShow;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_trackAnswer;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_trackDelete;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_trackEdit;

    public $allowedOrgs;

    /**
     * @var \Gems_Util_BasePath
     */
    protected $basepath;
    
    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

    /**
     * @var \MUtil_Html_Creator
     */
    public $creator             = null;

    public $currentOrgId;

    /**
     * Display text for track that can NOT be mailed, set in afterRegistry
     */
    protected $textMailable;
    /**
     * Display text for track that can be mailed, set in afterRegistry
     */
    protected $textNotMailable;

    /**
     * @var \Gems_Util
     */
    protected $util;

    protected function _addTooltip($element, $text, $placement = "auto")
    {
        $element->setAttrib('data-toggle', 'tooltip')
                ->setAttrib('data-placement', $placement)
                ->setAttrib('data-html', true) // For multiline tooltips
                ->setAttrib('title', $text);
    }

    protected function _getDeleteIcon($row, $trackParameterSource, $isSuccess = true)
    {
        $deleteTrackContainer = \MUtil_Html::create('div', array('class' => 'otherOrg pull-right', 'renderClosingTag' => true));
        if ($row['gr2o_id_organization'] != $this->currentOrgId) {
            $deleteTrackContainer[] = $this->loader->getOrganization($row['gr2o_id_organization'])->getName() . ' ';
        }
        if (array_key_exists($row['gr2o_id_organization'], $this->allowedOrgs) && $this->_trackDelete) {
            if ($isSuccess) {
                $caption = $this->_("Delete %s!");
                $icon    = 'trash';
            } else {
                $caption = $this->_("Undelete %s!");
                $icon    = 'recycle';
            }
            $deleteLink = \MUtil_Html::create('i', array(
                'class'            => 'fa fa-' . $icon . ' deleteIcon',
                'renderClosingTag' => true
            ));
            $this->_addTooltip($deleteLink, sprintf($caption, $this->_(array('track','', 1))), 'left');

            $link = $this->createMenuLink($trackParameterSource, 'track', 'delete-track', $deleteLink, $this->_trackDelete);
            $link->setAttrib('onClick', 'event.cancelBubble = true;');
            $deleteTrackContainer[] = $link;
        }

        return $deleteTrackContainer;
    }

    protected function _getEditIcon($row, $trackParameterSource)
    {
        if (array_key_exists($row['gr2o_id_organization'], $this->allowedOrgs) && $this->_trackEdit) {
            $editLink = \MUtil_Html::create('i', array(
                'class'            => 'fa fa-pencil',
                'renderClosingTag' => true
            ));
            $this->_addTooltip($editLink, $this->_("Edit track"), 'right');

            $link = $this->createMenuLink($trackParameterSource, 'track', 'edit-track', $editLink, $this->_trackEdit);
            $link->setAttrib('onClick', 'event.cancelBubble = true;');
        } else {
            // When org not allowed, dont add the link, so the track will just open
            $link = \MUtil_Html::create('span', array('class' => 'fa fa-pencil', 'renderClosingTag' => true));
        }
        return $link;
    }

    protected function _getMailIcon($row)
    {
        if (!array_key_exists('gr2t_mailable', $row)) {
            return null;
        }

        $tooltipText    = $row['gr2t_mailable'] == 0 ? $this->textNotMailable : $this->textMailable;
        $icon           = \MUtil_Html::create('i', array('class' => 'fa fa-envelope-o fa-fw', 'renderClosingTag' => true));
        $mailableIcon   = array();
        $mailableIcon[] = $icon;

        if ($row['gr2t_mailable'] == 0) {
            $icon           = \MUtil_Html::create('i', array('class' => 'fa fa-close fa-fw icon-danger', 'renderClosingTag' => true));
            $mailableIcon[] = $icon;
        }
        $this->_addTooltip($icon, $tooltipText, 'right');

        return $mailableIcon;
    }

    /**
     * Initialize the view
     *
     * Make sure the needed javascript is loaded
     *
     * @param \Zend_View $view
     */
    protected function _initView($view)
    {
        $baseUrl = $this->basepath->getBasePath();

        // Make sure we can use jQuery
        \MUtil_JQuery::enableView($view);

        // Now add the scrollTo plugin so we can scroll to today
        $view->headScript()->appendFile($baseUrl . '/gems/js/jquery.scrollTo.min.js');

        /*
         * And add some initialization:
         *  - Hide all tokens initially (accessability, when no javascript they should be visible)
         *  - If there is a day labeled today, scroll to it (prevents errors when not visible)
         */
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.trafficlight.js');
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.verticalExpand.js');
        $view->headScript()->appendFile($baseUrl . '/gems/js/gems.respondentAnswersModal.js');
    }

    /**
     * Copied from \Gems_Token, to save overhead of loading a token just for this check
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isCompleted($tokenData)
    {
        return ($tokenData['token_status'] == "A");
    }

    /**
     * Are we past the valid until date
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isMissed($tokenData)
    {
        return in_array($tokenData['token_status'], ['M', 'I']);
    }

    /**
     * Are we past the valid from date but before the valid until date?
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isValid($tokenData)
    {
        return in_array($tokenData['token_status'], ['O', 'P']);
    }

    protected function _loadData()
    {
        $model = $this->getModel();
        $model->trackUsage();

        $items = array(
            'gto_id_respondent_track',
            'gto_valid_from',
            'gto_valid_until',
            'gr2t_start_date',
            'gtr_track_name',
            'gr2t_track_info',
            'gto_id_token',
            'gto_round_description',
            'forgroup',
            'gsu_survey_name',
            'gto_completion_time',
            'gr2o_patient_nr',
            'gr2o_id_organization',
            'gro_icon_file',
            'gto_icon_file',
            'gr2t_mailable',        // For mail icon
            'gr2t_reception_code',  // For deleted tracks
            'gr2t_comment',         // For deleted tracks
            'gto_result',
            'ggp_respondent_members' // For edit vs take (respondent / staff)
        );
        foreach ($items as $item)
        {
            $model->get($item);
        }

        return $model->load(true, $this->_fixedSort);
    }

    public function addToken($tokenData)
    {
        // We add all data we need so no database calls needed to load the token
        $tokenDiv = $this->creator->div(array('class' => 'zpitem', 'renderClosingTag' => true));
        $innerDiv = $tokenDiv->div(array('class' => 'tokenwrapper', 'renderClosingTag' => true));

        $toolsDiv = $this->creator->div(array('class' => 'tools', 'renderClosingTag' => true));
        $innerDiv[] = $toolsDiv;

        $this->getToolIcons($toolsDiv, $tokenData);
        $this->addTokenIcon($toolsDiv, $tokenData);

        $tokenClass = $this->util->getTokenData()->getStatusClass($tokenData['token_status']);

        $tokenLink = null;

        switch ($tokenData['token_status']) {
            case 'A': // Answered
                $tokenLink = $this->createMenuLink($tokenData + array('gto_in_source' => 1), 'track', 'answer', '', $this->_trackAnswer);
                $tooltip = array(sprintf($this->_('Completed') . ': %s', $tokenData['gto_completion_time']->get($this->_dateTimeFormat)));
                if (!empty($tokenData['gto_result'])) {
                    $tooltip[] = \MUtil_Html::raw('<br/>');
                    $tooltip[] = sprintf($this->_('Result') .': %s', $tokenData['gto_result']);
                }
                $this->_completed++;
                if ($tokenLink) {
                    $tokenLink->appendAttrib('class', 'inline-answers');
                }
                break;

            case 'O': // Open
            case 'P': // Partial
                $tokenLink = $this->createMenuLink($tokenData, 'ask', 'take', '', $this->_takeSurvey);
                if ($tokenData['ggp_respondent_members'] == 1) {
                    $tokenLink = $this->createMenuLink($tokenData, 'track', 'show', '', $this->_tokenShow);
                }
                if (is_null($tokenData['gto_valid_until'])) {
                    $tooltip = $this->_('Does not expire');
                } else {
                    $tooltip = sprintf($this->_('Open until %s'), $tokenData['gto_valid_until']->get($this->_dateTimeFormat));
                }
                $this->_open++;
                break;

            case 'M': // Missed
            case 'I': // Incomplete
                $tokenLink = $this->createMenuLink($tokenData + array('id_type' => 'token', 'grc_success' => 1), 'track', 'edit', '', $this->_tokenEdit);
                $tooltip = sprintf($this->_('Missed since %s'), $tokenData['gto_valid_until']->get($this->_dateTimeFormat));
                $this->_missed++;
                break;

            case 'W': //Waiting
                $tokenLink = $this->createMenuLink($tokenData + array('id_type' => 'token', 'grc_success' => 1), 'track', 'edit', '', $this->_tokenEdit);
                $tooltip = sprintf($this->_('Valid from %s'), $tokenData['gto_valid_from']->get($this->_dateTimeFormat));
                $this->_future++;

            default:
                break;
        }

        if (empty($tokenLink)) {
            $tokenClass .= ' disabled';
            $tokenLink = $this->creator->div(array('class'=>'disabled'));
        }
        $tokenDiv->appendAttrib('class', $tokenClass);
        $innerDiv[] = $tokenLink;

        $this->_addTooltip($tokenLink, $tooltip, 'auto top');
        $tokenLink[] = $tokenData['gsu_survey_name'];

        return $tokenDiv;
    }

    protected function addTokenIcon($toolsDiv, $tokenData)
    {
        $iconFile = '';
        if (!empty($tokenData['gto_icon_file'])) {
            $iconFile = $tokenData['gto_icon_file'];
        } elseif (!empty($tokenData['gro_icon_file'])) {
            $iconFile = $tokenData['gro_icon_file'];
        }
        if (!empty($iconFile)) {
            $toolsDiv->img(array('src' => $tokenData['gto_icon_file'], 'class' => 'icon'));
        }
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        // Load the display dateformat
        $dateOptions       = \MUtil_Model_Bridge_FormBridge::getFixedOptions('date');
        $dateTimeOptions   = \MUtil_Model_Bridge_FormBridge::getFixedOptions('datetime');
        $this->_dateFormat = $dateOptions['dateFormat'];
        $this->_dateTimeFormat = $dateTimeOptions['dateFormat'];

        $this->creator = \MUtil_Html::getCreator();

        // find the menu items only once for more efficiency
        $this->_trackAnswer  = $this->findMenuItem('track', 'answer');
        $this->_trackEdit    = $this->findMenuItem('track', 'edit-track');
        $this->_trackDelete  = $this->findMenuItem('track', 'delete-track');

        $this->_surveyAnswer = $this->findMenuItem('survey', 'answer');
        $this->_takeSurvey   = $this->findMenuItem('ask', 'take');

        $this->_tokenCorrect = $this->findMenuItem('track', 'correct');
        $this->_tokenDelete  = $this->findMenuItem('track', 'delete');
        $this->_tokenEdit    = $this->findMenuItem('track', 'edit');
        $this->_tokenPreview = $this->findMenuItem('track', 'questions');
        $this->_tokenShow    = $this->findMenuItem('track', 'show');
        $this->_overview     = $this->findMenuItem('respondent', 'overview');

        // Initialize the tooltips
        $this->textNotMailable = $this->_("May not be mailed");
        $this->textMailable    = $this->_("May be mailed");

        // Initialize lookup for allowed and current organization
        $this->allowedOrgs  = $this->loader->getCurrentUser()->getAllowedOrganizations();
        $this->currentOrgId = $this->loader->getCurrentUser()->getCurrentOrganizationId();
    }

    /**
     * Copied, optimised to we use the optional $menuItem we stored in _initView instead
     * of doing the lookup again and again
     *
     * @param type $parameterSource
     * @param type $controller
     * @param type $action
     * @param type $label
     * @param type $menuItem
     * @return \MUtil_Html_AElement
     */
    public function createMenuLink($parameterSource, $controller, $action = 'index', $label = null, $menuItem = null)
    {
        if (!is_null($menuItem) || $menuItem = $this->findMenuItem($controller, $action)) {
            $item = $menuItem->toActionLinkLower($this->request, $parameterSource, $label);
            if (is_object($item)) {
                $item->setAttrib('class', '');
            }
            return $item;
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel()
    {
        $model = parent::createModel();

        if (!$model->has('forgroup')) {
            $model->addColumn('gems__groups.ggp_name', 'forgroup');
        }

        return $model;
    }

    protected function finishGroup($progressDiv)
    {
        if (is_null($progressDiv)) {
            return;
        }

        if ($this->_missed > 0) {
            $progressDiv->div($this->_missed, array('class' => 'missed'));
        }
        if ($this->_completed > 0) {
            $progressDiv->div($this->_completed, array('class' => 'answered'));
        }
        if ($this->_open > 0) {
            $progressDiv->div($this->_open, array('class' => 'open'));
        }
        if ($this->_future > 0) {
            $progressDiv->div($this->_future, array('class' => 'waiting'));
        }

        $this->_missedTrack    = $this->_missedTrack + $this->_missed;
        $this->_completedTrack = $this->_completedTrack + $this->_completed;
        $this->_openTrack      = $this->_openTrack + $this->_open;
        $this->_futureTrack    = $this->_futureTrack + $this->_future;

        $this->_completed = 0;
        $this->_missed    = 0;
        $this->_open      = 0;
        $this->_future    = 0;
    }

    protected function finishTrack($progressDiv)
    {
        if (!is_null($progressDiv)) {
            $total = max($this->_completedTrack + $this->_openTrack + $this->_missedTrack + $this->_futureTrack, 1);

            $div = $progressDiv->div($this->_missedTrack, array(
                'class'            => 'progress-bar missed',
                'style'            => 'width: ' . $this->_missedTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s missed"), $this->_missedTrack) , 'top');
            $div = $progressDiv->div($this->_completedTrack, array(
                'class'            => 'progress-bar answered',
                'style'            => 'width: ' . $this->_completedTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s completed"), $this->_completedTrack), 'top');
            $div = $progressDiv->div($this->_openTrack, array(
                'class'            => 'progress-bar open',
                'style'            => 'width: ' . $this->_openTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s open"), $this->_openTrack), 'top');
            $div = $progressDiv->div($this->_futureTrack, array(
                'class'            => 'progress-bar waiting',
                'style'            => 'width: ' . $this->_futureTrack / $total * 100 . '%;',
                'renderClosingTag' => true));
            $this->_addTooltip($div, sprintf($this->_("%s upcoming"), $this->_futureTrack), 'top');
        }

        $this->_missedTrack    = 0;
        $this->_completedTrack = 0;
        $this->_openTrack      = 0;
        $this->_futureTrack    = 0;

        return;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $this->_initView($view);

        $main = $this->creator->div(array('class' => 'panel panel-default', 'id' => 'trackwrapper', 'renderClosingTag' => true));

        //$main->div(array('id' => 'modalpopup', 'renderClosingTag' => true));

        $currentTrackId  = \Gems_Cookies::get($this->request, 'track_idx');
        $data            = $this->_loadData();
        $doelgroep       = null;
        $lastDate        = null;
        $lastDescription = null;
        $now             = new \MUtil_Date();
        $progressDiv     = null;
        $respTrackId     = 0;
        $today           = $now->get($this->_dateFormat);
        $trackProgress   = null;
        $minIcon         = \MUtil_Html::create('span', array('class' => 'fa fa-plus-square', 'renderClosingTag' => true));
        $summaryIcon     = \MUtil_Html::create('i', array('class' => 'fa fa-list-alt fa-fw', 'renderClosingTag' => true));
        $trackIds        = array_column($data, 'gto_id_respondent_track', 'gto_id_respondent_track');

        // Check for cookie set for this patient
        if (! isset($trackIds[$currentTrackId])) {
            $currentTrackId =  reset($trackIds);
        }
        
        // The normal loop
        foreach ($data as $row)
        {
            if ($respTrackId !== $row['gto_id_respondent_track']) {
                if (isset($day) && new \MUtil_Date($lastDate, 'dd-MM-y') < $now) {
                    $day->class .= ' today';
					unset($day);
                }
                $progressDiv = $this->finishGroup($progressDiv);
                $this->finishTrack($trackProgress);

                $doelgroep            = null;
                $lastDate             = null;
                $lastDescription      = null;
                $respTrackId          = $row['gto_id_respondent_track'];
                $trackParameterSource = array(
                    'gr2t_id_respondent_track' => $row['gto_id_respondent_track'],
                    'gr2o_patient_nr'          => $row['gr2o_patient_nr'],
                    'gr2o_id_organization'     => $row['gr2o_id_organization'],
                    'can_edit'                 => 1
                );

                if ($row['gto_id_respondent_track'] == $currentTrackId) {
                    $caretClass = "fa-chevron-down";
                    $bodyStyle  = "";  
                } else {
                    $caretClass = "fa-chevron-right";
                    $bodyStyle  = "display: none;";
                }
                
                $track         = $main->div(array('class' => 'panel panel-default traject verticalExpand'));

                $trackHeading  = $track->div(array('class' => 'panel-heading header', 'renderClosingTag' => true));

                $trackTitle    = \MUtil_Html::create('span', array('class' => 'title'));
                $trackTitle[]  = ' ' . $row['gtr_track_name'];
                $trackTitle[]  = \MUtil_Html::create('span', array('class' => "header-caret fa fa-fw " . $caretClass, 'renderClosingTag' => true));

                $trackReceptionCode = $this->loader->getUtil()->getReceptionCode($row['gr2t_reception_code']);
                if (!$trackReceptionCode->isSuccess()) {
                    $track->class = $track->class . ' deleted';
                    $description = $trackReceptionCode->getDescription();
                    if (!empty($row['gr2t_comment'])) {
                        $description .= sprintf(' (%s)', $row['gr2t_comment']);
                    }
                    $trackTitle[] = \MUtil_Html::create('div', $description, array('class'=>'description'));
                }

                $trackHeader   = $trackHeading->h3(array('class' => "panel-title", 'renderClosingTag' => true));
                $trackHeader[] = $this->_getEditIcon($row, $trackParameterSource);
                $trackHeader[] = $this->_getMailIcon($row);
                $trackHeader[] = $trackTitle;
                $trackHeader[] = $this->_getDeleteIcon($row, $trackParameterSource, $trackReceptionCode->isSuccess());

                if ($row['gr2t_start_date'] instanceof \Zend_Date) {
                    $trackStartDate = $row['gr2t_start_date']->get($this->_dateFormat);
                } else {
                    $trackStartDate = $this->_('n/a');
                }
                $trackHeading->div($row['gr2t_track_info'], array('renderClosingTag' => true));
                $trackHeading->div($this->_('Start date') . ': ' . $trackStartDate);
                $trackProgress = $trackHeading->div(array('class' => 'progress pull-right', 'renderClosingTag' => true));

                $container    = $track->div(array('class' => 'panel-body', 'style' => $bodyStyle));
                $subcontainer = $container->div(array('class' => 'objecten', 'renderClosingTag' => true));
            }

            $date = $row['gto_valid_from'];
            if ($date instanceof \Zend_Date) {
                $date = $date->get($this->_dateFormat);
            } else {
                continue;
            }

            $description = $row['gto_round_description'];
            if (is_null($description)) $description = '';
            if (/* $date !== $lastDate || */ $lastDescription !== $description || !isset($day)) {
                $last = new \MUtil_Date($lastDate, 'dd-MM-y');
                if (isset($day) && $last < $now && $row['gto_valid_from'] > $now) {
                    $day->class .= ' today';
                }
                $lastDescription = $description;
                $progressDiv     = $this->finishGroup($progressDiv);
                $lastDate        = $date;
                $class           = 'object';
                if ($date == $today) {
                    $class .= ' today';
                }
                $day = $subcontainer->div(array('class' => $class, 'renderClosingTag' => true));
                $this->_addTooltip($summaryIcon, $this->_('Summary'), 'auto top');
                $params = [
                    'gto_id_respondent_track' => $row['gto_id_respondent_track'],
                    'gto_round_description'   => urlencode(str_replace('/', '&#47;', $description))
                ];
                if ($this->_overview) {
                    $summaryLink = $this->createMenuLink(
                        [
                            'gr2o_patient_nr' => $row['gr2o_patient_nr'],
                            'gr2o_id_organization' => $row['gr2o_id_organization'],
                            'RouteReset' => true,
                            ],  
                        'respondent', 
                        'overview', 
                        $summaryIcon, 
                        $this->_overview);
                    $summaryLink->href->add($params);
                    $summaryLink->target = 'inline';
                } else {
                    $summaryLink = \MUtil_Html::create('div', $summaryIcon, array('renderClosingTag' => true));
                }
                $summaryLink->class='pull-right';
                $day->h5(array($summaryLink, ucfirst($description)));
                $day->h6($date);

                $doelgroep = null;
            } elseif (isset($day) && $lastDate !== $date) {
                // When we have a new start date, add the date and start a new group
                $day->h6($date);
                $lastDate = $date;
                $doelgroep = null;
            }

            if ($doelgroep !== $row['forgroup']) {
                $this->finishGroup($progressDiv);
                $doelgroep    = $row['forgroup'];
                $doelgroepDiv = $day->div(array('class' => 'actor', 'renderClosingTag' => true));
                $doelgroepDiv->h6(array($minIcon, $doelgroep));
                $progressDiv  = $doelgroepDiv->div(array('class' => 'zplegenda', 'renderClosingTag' => true));
                $tokenDiv     = $doelgroepDiv->div(array('class' => 'zpitems', 'renderClosingTag' => true));
            }

            $tokenDiv[] = $this->addToken($row);
        }
        if (isset($day) && new \MUtil_Date($lastDate, 'dd-MM-y') < $now) {
            $day->class .= ' today';
        }
        $progressDiv = $this->finishGroup($progressDiv);
        $this->finishTrack($trackProgress);

        return $main;
    }

    /**
     *
     * @param \MUtil_Html $toolsDiv
     * @param array $token
     */
    public function getToolIcons($toolsDiv, $token)
    {
        static $correctIcon;
        static $showIcon;

        if (!isset($correctIcon)) {
            $correctIcon = \MUtil_Html::create('i', array(
                    'class'            => 'fa fa-fw fa-pencil dropdown-toggle',
                    'renderClosingTag' => true
                ));
        }

        if (!isset($showIcon)) {
            $plusIcon = \MUtil_Html::create('i', array(
                    'class'            => 'fa fa-fw fa-ellipsis-h dropdown-toggle',
                    'renderClosingTag' => true
                ));
        }        

        // When not completed we have no correct
        if ($this->_isCompleted($token)) {
            $correctLink = $this->createMenuLink($token + ['is_completed' => 1, 'grc_success' => 1], 'track', 'correct', $correctIcon, $this->_tokenCorrect);
            if ($correctLink) {
                $dropUp = $toolsDiv->div(array('class' => 'dropdown dropup pull-right', 'renderClosingTag' => true));
                $this->_addTooltip($dropUp, ucfirst($this->_tokenCorrect->get('label')));
                $dropUp->append($correctLink);
            }
        }

        $showLink = $this->createMenuLink($token, 'track', 'show', $plusIcon, $this->_tokenShow);
        if ($showLink) {
            $dropUp = $toolsDiv->div(array('class' => 'dropdown dropup pull-right', 'renderClosingTag' => true));
            $this->_addTooltip($dropUp, $this->_('Details'));
            $dropUp->append($showLink);
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        return $this->respondent && $this->request;
    }

    /**
     * Copied from parent, adjusted to also show inactive tracks with ok and completed tokens
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        $filter['gto_id_respondent']   = $this->respondent->getId();
        if (is_array($this->forOtherOrgs)) {
            $filter['gto_id_organization'] = $this->forOtherOrgs;
        } elseif (true !== $this->forOtherOrgs) {
            $filter['gto_id_organization'] = $this->respondent->getOrganizationId();
        }

        // Filter for valid track reception codes
        $filter[] = 'gr2t_reception_code IN (SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_success = 1) OR (gto_completion_time IS NOT NULL)';
        $filter['grc_success'] = 1;
        // Active round
        // or
        // no round
        // or
        // token is success and completed
        $filter[] = 'gro_active = 1 OR gro_active IS NULL OR (gto_completion_time IS NOT NULL)';
        $filter['gsu_active']  = 1;

        // NOTE! $this->model does not need to be the token model, but $model is a token model
        $tabFilter = $this->model->getMeta('tab_filter');
        if ($tabFilter) {
            $model->addFilter($tabFilter);
        }

        $model->addFilter($filter);

        // \MUtil_Echo::track($model->getFilter());

        $this->processSortOnly($model);
    }
}
