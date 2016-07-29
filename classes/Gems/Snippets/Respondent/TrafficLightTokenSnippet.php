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
class Gems_Snippets_Respondent_TrafficLightTokenSnippet extends \Gems_Snippets_RespondentTokenSnippet
{
    
    public $browse = false;

    /**
     * @var \MUtil_Html_Creator
     */
    public $creator             = null;
    protected $_fixedSort       = array(
        'gr2t_start_date'         => SORT_DESC,
        'gto_id_respondent_track' => SORT_DESC,
        'gto_round_order'         => SORT_ASC,
        'gto_valid_from'          => SORT_ASC,
        'gto_round_description'   => SORT_ASC,
        'forgroup'                => SORT_ASC,
        'gto_round_order'         => SORT_ASC
    );
    protected $_fixedFilter     = array(
        //'gto_valid_from <= NOW()'
    );
    protected $_completed       = 0;
    protected $_open            = 0;
    protected $_missed          = 0;
    protected $_future          = 0;
    protected $_track_completed = 0;
    protected $_track_open      = 0;
    protected $_track_missed    = 0;
    protected $_track_future    = 0;

    /**
     * The display format for the date
     *
     * @var string
     */
    protected $_dateFormat;

    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_surveyAnswer;

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


    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    protected $_takeSurvey;

    /**
     * Initialize the view
     *
     * Make sure the needed javascript is loaded
     *
     * @param \Zend_View $view
     */
    protected function _initView($view) 
    {
        $baseUrl = \GemsEscort::getInstance()->basepath->getBasePath();

        // Make sure we can use jQuery
        \MUtil_JQuery::enableView($view);

        // Now add the scrollTo plugin so we can scroll to today
        $view->headScript()->appendFile($baseUrl . '/gems/js/jquery.scrollTo.min.js');

        /*
         * And add some initialization:
         *  - Hide all tokens initially (accessability, when no javascript they should be visible)
         *  - If there is a day labeled today, scroll to it (prevents errors when not visible)
         */
        $view->headScript()->appendFile($baseUrl . '/gems/js/trafficlight.js');
    }

    /**
     * Copied from \Gems_Token, to save overhead of loading a token just for this check
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isCompleted($tokenData)
    {
        return isset($tokenData['gto_completion_time']) && $tokenData['gto_completion_time'];
    }

    /**
     * Are we past the valid until date
     *
     * @param array $tokenData
     * @return boolean
     */
    protected function _isMissed($tokenData)
    {
        $missed = false;

        if (isset($tokenData['gto_valid_until'])) {
            $date = $tokenData['gto_valid_until'];
            if ($date instanceof \MUtil_Date) {
                $now = new \MUtil_Date();
                if ($now->getTimestamp() - $date->getTimestamp() > 0) {
                    $missed = true;
                }
            }
        }

        return $missed;
    }

    public function addToken($tokenData)
    {
        // We add all data we need so no database calls needed to load the token
        $tokenDiv = $this->creator->div(array('class' => 'zpitem', 'renderClosingTag' => true));

        $tokenLink = null;

        if ($this->_isCompleted($tokenData)) {
            $status = $this->creator->span($this->translate->_('Answered'), array('class' => 'success'));
            $status = '';
            $tokenLink = $this->createMenuLink($tokenData, 'track', 'answer', $status, $this->_trackAnswer);
        } else {
            $status    = $this->creator->span($this->translate->_('Fill in'), array('class' => 'warning'));
            $status    = '';
            $tokenLink = $this->createMenuLink($tokenData, 'ask', 'take', $status, $this->_takeSurvey);
        }

        if (!empty($tokenLink)) {
            if ($this->_isCompleted($tokenData)) {
                $this->_completed++;
                $tokenDiv->appendAttrib('class', ' success');
                $tokenLink->target = 'inline';
            } else {
                $this->_open++;
                $tokenDiv->appendAttrib('class', ' warning');
                $tokenLink->target = '_self'; //$tokenData['gto_id_token'];
            }
            $tokenDiv[] = $tokenLink;
        } else {
            if ($this->_isMissed($tokenData)) {
                $this->_missed++;
                $tokenDiv->appendAttrib('class', ' danger');
            } else {
                $this->_future++;
                $tokenDiv->appendAttrib('class', ' info');
            }
            $status    = '';
            $tokenLink = $tokenDiv->a('#', $status);
        }
        $survey = array($tokenData['gsu_survey_name']);
        if (!empty($tokenData['gto_icon_file'])) {
            array_unshift($survey, \MUtil_Html::create('img', array('src' => $tokenData['gto_icon_file'], 'class' => 'icon')));
        } elseif (!empty($tokenData['gro_icon_file'])) {
            array_unshift($survey, \MUtil_Html::create('img', array('src' => $tokenData['gro_icon_file'], 'class' => 'icon')));
        }

        $tokenLink[] = $survey;

        return $tokenDiv;
    }

    public function afterRegistry() 
    {
        parent::afterRegistry();
        
        // Load the display dateformat
        $dateOptions       = \MUtil_Model_Bridge_FormBridge::getFixedOptions('date');
        $this->_dateFormat = $dateOptions['dateFormat'];

        $this->creator = \Gems_Html::init();

        // find the menu items only once for more efficiency
        $this->_trackAnswer  = $this->findMenuItem('track', 'answer');
        $this->_trackEdit    = $this->findMenuItem('track', 'edit-track');
        $this->_trackDelete  = $this->findMenuItem('track', 'delete-track');
        $this->_surveyAnswer = $this->findMenuItem('survey', 'answer');
        $this->_takeSurvey   = $this->findMenuItem('ask', 'take');
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

    public function createModel() 
    {
        $model = parent::createModel();

        if (!$model->has('forgroup')) {
            $model->addColumn('gems__groups.ggp_name', 'forgroup');
        }

        return $model;
    }

    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $this->_initView($view);

        $main = $this->creator->div(array('class' => 'panel panel-default', 'id' => 'trackwrapper', 'renderClosingTag' => true));

        $main->div(array('id' => 'modalpopup', 'renderClosingTag' => true));

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
        );
        foreach ($items as $item)
        {
            $model->get($item);
        }

        $data            = $model->load(true, $this->_fixedSort);
        $lastDate        = null;
        $lastDescription = null;
        $doelgroep       = null;
        $now             = new \MUtil_Date();
        $today           = $now->get($this->_dateFormat);
        $progressDiv     = null;
        $respTrackId     = 0;
        $trackProgress   = null;

        $currentOrg  = $this->loader->getCurrentUser()->getCurrentOrganizationId();
        $allowedOrgs = $this->loader->getCurrentUser()->getAllowedOrganizations();
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

                $lastDate        = null;
                $doelgroep       = null;
                $lastDescription = null;
                //if ($respTrackId == 0) {
                //    $track = $main->div(array('class' => 'panel panel-default traject active'));
                //} else {
                $track        = $main->div(array('class' => 'panel panel-default traject'));
                //}
                $respTrackId  = $row['gto_id_respondent_track'];
                $trackHeading = $track->div(array('class' => 'panel-heading', 'renderClosingTag' => true));
                $trackHeader  = $trackHeading->h3(array('class' => "panel-title",'renderClosingTag' => true));

                $trackParameterSource = array(
                        'gr2t_id_respondent_track' => $row['gto_id_respondent_track'],
                        'gr2o_patient_nr'          => $row['gr2o_patient_nr'],
                        'gr2o_id_organization'     => $row['gr2o_id_organization'],
                        'can_edit'                 => 1
                        );

                if (array_key_exists($row['gr2o_id_organization'], $allowedOrgs) && $this->_trackEdit) {
                    $editLink = \MUtil_Html::create('i', array(
                        'class' => 'fa fa-pencil',
                        'renderClosingTag' => true,
                        'data-toggle'      => 'tooltip',
                        'data-placement'   => 'right',
                        'title'            => $this->_("Edit track")
                        ));

                    $link = $this->createMenuLink($trackParameterSource, 'track', 'edit-track', $editLink, $this->_trackEdit);
                    $link->setAttrib('onClick', 'event.cancelBubble = true;');
                } else {
                    // When org not allowed, dont add the link, so the track will just open
                    $link = \MUtil_Html::create('span', array('class' => 'fa fa-pencil', 'renderClosingTag' => true));
                }
                $trackHeader[] = $link;
                $title = \MUtil_Html::create('span', array('class' => 'title'));
                $title[] = ' ' . $row['gtr_track_name'];
                $title[] = \MUtil_Html::create('span', array('class' => "fa fa-chevron-down fa-fw", 'renderClosingTag' => true));
                $trackHeader[] = $title;

                $deleteTrackContainer = \MUtil_Html::create('div', array('class' => 'otherOrg pull-right', 'renderClosingTag' => true));
                if ($row['gr2o_id_organization'] != $currentOrg) {
                    $org = $this->loader->getOrganization($row['gr2o_id_organization'])->getName();
                    $deleteTrackContainer[] = $org . ' ';
                }
                if (array_key_exists($row['gr2o_id_organization'], $allowedOrgs) && $this->_trackDelete) {
                    $deleteLink = \MUtil_Html::create('i', array(
                        'class' => 'fa fa-trash deleteIcon',
                        'renderClosingTag' => true,
                        'data-toggle'      => 'tooltip',
                        'data-placement'   => 'left',
                        'title'            => $this->_("Delete track")
                        ));

                    $link = $this->createMenuLink($trackParameterSource, 'track', 'delete-track', $deleteLink, $this->_trackDelete)
                                 ->setAttrib('onClick', 'event.cancelBubble = true;');
                    $deleteTrackContainer[] = $link;
                }
                $trackHeader[]         = $deleteTrackContainer;

                if ($row['gr2t_start_date'] instanceof Zend_Date) {
                    $trackStartDate = $row['gr2t_start_date']->get($this->_dateFormat);
                } else {
                    $trackStartDate = $this->_('n/a');
                }
                $trackHeading->div($row['gr2t_track_info'], array('renderClosingTag' => true));
                $trackHeading->div($this->_('Start date') . ': ' . $trackStartDate, array('renderClosingTag' => true));
                $trackProgress = $trackHeading->div(array('class' => 'progress pull-right', 'renderClosingTag' => true));

                $container    = $track->div(array('class' => 'panel-body', 'renderClosingTag' => true));
                $subcontainer = $container->div(array('class' => 'objecten', 'renderClosingTag' => true));
            }
            $date = $row['gto_valid_from'];
            if ($date instanceof \Zend_Date) {
                $date = $date->get($this->_dateFormat);
            } else {
                continue;
            }

            $description = $row['gto_round_description'];
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
                $day->h4(ucfirst($row['gto_round_description']));
                $day->h5($date);

                $doelgroep = null;
            } elseif (isset($day) && $lastDate !== $date) {
                // When we have a new start date, add the date and start a new group
                $day->h5($date);
                $lastDate = $date;
                $doelgroep = null;
            }

            if ($doelgroep !== $row['forgroup']) {
                $progressDiv  = $this->finishGroup($progressDiv);
                $doelgroep    = $row['forgroup'];
                $doelgroepDiv = $day->div(array('class' => 'actor', 'renderClosingTag' => true));
                //$progressDiv  = $doelgroepDiv->div(array('class' => 'progress'));
                $minIcon      = \MUtil_Html::create('span', array('class' => 'fa fa-plus-square', 'renderClosingTag' => true));
                $title        = $doelgroepDiv->h5(array($minIcon, $doelgroep));
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

    protected function finishGroup($progressDiv)
    {
        if (!is_null($progressDiv)) {
            $total = $this->_completed + $this->_open + $this->_missed;
            if (!$this->_completed == 0) {
                $progressDiv->div(array('class' => 'success'))->append($this->_completed);
            }
            if (!$this->_open == 0) {
                $progressDiv->div(array('class' => 'warning'))->append($this->_open);
            }
            if (!$this->_missed == 0) {
                $progressDiv->div(array('class' => 'danger'))->append($this->_missed); //->setAttrib('style', sprintf('width: %s%%;', $this->_missed / $total * 100));
            }
            if (!$this->_future == 0) {
                $progressDiv->div(array('class' => 'info'))->append($this->_future);
            }
        }

        $this->_track_completed = $this->_track_completed + $this->_completed;
        $this->_track_open      = $this->_track_open + $this->_open;
        $this->_track_missed    = $this->_track_missed + $this->_missed;
        $this->_track_future    = $this->_track_future + $this->_future;

        $this->_completed = 0;
        $this->_open      = 0;
        $this->_missed    = 0;
        $this->_future    = 0;

        return;
    }

    protected function finishTrack($progressDiv) {
        if (!is_null($progressDiv)) {
            $total = max($this->_track_completed + $this->_track_open + $this->_track_missed + $this->_track_future, 1);

            $progressDiv->div($this->_track_completed, array(
                'class'            => 'progress-bar progress-bar-success',
                'style'            => 'width: ' . $this->_track_completed / $total * 100 . '%;',
                'data-toggle'      => 'tooltip',
                'data-placement'   => 'top',
                'title'            => sprintf($this->_("%s completed"), $this->_track_completed),
                'renderClosingTag' => true));
            $progressDiv->div($this->_track_open, array(
                'class'            => 'progress-bar progress-bar-warning',
                'style'            => 'width: ' . $this->_track_open / $total * 100 . '%;',
                'data-toggle'      => 'tooltip',
                'data-placement'   => 'top',
                'title'            => sprintf($this->_("%s open"), $this->_track_open),
                'renderClosingTag' => true));
            $progressDiv->div($this->_track_missed, array(
                'class'            => 'progress-bar progress-bar-danger',
                'style'            => 'width: ' . $this->_track_missed / $total * 100 . '%;',
                'data-toggle'      => 'tooltip',
                'data-placement'   => 'top',
                'title'            => sprintf($this->_("%s missed"), $this->_track_missed),
                'renderClosingTag' => true));
            $progressDiv->div($this->_track_future, array(
                'class'            => 'progress-bar progress-bar-info',
                'style'            => 'width: ' . $this->_track_future / $total * 100 . '%;',
                'data-toggle'      => 'tooltip',
                'data-placement'   => 'top',
                'title'            => sprintf($this->_("%s upcoming"), $this->_track_future),
                'renderClosingTag' => true));
        }

        $this->_track_completed = 0;
        $this->_track_open      = 0;
        $this->_track_missed    = 0;
        $this->_track_future    = 0;

        return;
    }

    public function hasHtmlOutput() {
        return $this->respondent && $this->request;
    }
}