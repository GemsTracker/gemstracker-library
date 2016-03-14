<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
    /**
     * The display format for the date
     *
     * @var string
     */
    protected $_dateFormat;

    protected $_fixedFilter = array(
        'gto_valid_from <= NOW()'
    );

    protected $_fixedSort   = array(
        'gr2t_start_date'         => SORT_DESC,
        'gto_id_respondent_track' => SORT_DESC,
        'gto_valid_from'          => SORT_ASC,
        'gto_round_description'   => SORT_ASC,
        'forgroup'                => SORT_ASC
    );

    protected $_completed   = 0;
    protected $_open        = 0;
    protected $_missed      = 0;

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
    protected $_takeSurvey;

    public $browse = false;

    /**
     * @var \MUtil_Html_Creator
     */
    public $creator         = null;

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
        $view->headScript()->appendScript('

    // Click track
    $(".traject .panel-heading").click(function(){
        $(this).next().toggle();
        if($(this).next().is(":visible")) {
            $(this).find("h3 span").removeClass("fa-chevron-right").addClass("fa-chevron-down");
            // Close all days
            $(this).next().find(".actor").each(function(){if ( $(this).find(".zpitems").is(":visible") ) { $(this).find("h5").click(); }});
            // Scroll to today
            $(this).next().find(".object.today").each(function(){
                // Open current day
                $(this).children(".actor").each(function(){if ( $(this).find(".zplegenda").is(":visible") ) { $(this).find("h5").click(); }});
                // Scroll to today
                $(this).parent().parent().scrollTo($(this),0, { offset: $(this).outerWidth(true)-$(this).parent().parent().innerWidth()} );    /* today is rightmost block */
            });
        } else {
            $(this).find("h3 span").addClass("fa-chevron-right").removeClass("fa-chevron-down");
        }
    });

    // Click day
    $(".object h4").click(function(){
        if ( $(this).parent().find(".actor h5 span").first().hasClass("fa-minus-square") ) {
            // First is open, now close first and all others
            $(this).parent().find(".actor h5 span").each(function(){
                if ( $(this).hasClass("fa-minus-square")) {
                    $(this).parent().click();
                }
            });
        } else {
            // First is closed, now open first and all others
            $(this).parent().find(".actor h5 span").each(function(){
                if ( $(this).hasClass("fa-plus-square")) {
                    $(this).parent().click();
                }
            });
        }
    });

    // Click actor
    $(".actor h5").click(function(){
        if ( $(this).find("span").first().hasClass("fa-plus-square") ) {
            $(this).find("span").removeClass("fa-plus-square").addClass("fa-minus-square");
            $(this).parent().find(".zplegenda").toggle(false);
            $(this).parent().find(".zpitems").toggle(true);
        } else {
            $(this).find("span").addClass("fa-plus-square").removeClass("fa-minus-square");
            $(this).parent().find(".zplegenda").toggle(true);
            $(this).parent().find(".zpitems").toggle(false);
        }
    });

    // Click legend
    $(".actor .zplegenda").click(function(){
        // delegate to actor
        $(this).parent().find("h5").click();
    });

    // Initially hide all zpitems so only zplegende remains visible
    $(".object .actor").children(".zpitems").toggle(false);

    // First close all tracks
    $(".traject .panel-heading").click();
    // and open the first one
    $(".traject .panel-heading").first().click();

    // Inline answers + printing dialog
    $(".zpitem.success a[target=\'inline\']").click(function(e){
        e.preventDefault();
        // Now open a new div, not #menu and bring it to the front
        // Add a close button to it, maybe the available tooltip can help here
        $("div#modalpopup").html("<div class=\'loading\'></div>"); // Make sure we show no old information
        $("div#modalpopup").load($(this).attr(\'href\'));
        $("div#modalpopup").dialog({
            modal: true,
            width: 500,
            position:{ my: "left top", at: "left top", of: "#main" },
            buttons: [
                {
                text: "Print",
                "class": "btn-primary",
                click: function() {
                        if ($(".modal").is(":visible")) {
                            var oldId = $(event.target).closest(".modal").attr("id");
                            var modalId = "modelprint";
                            $(event.target).closest(".modal").attr("id", modalId);
                            $("body").css("visibility", "hidden");
                            $("body #container").css("display", "none");
                            $("div#modalpopup").css("visibility", "visible");
                            $("#" + modalId).removeClass("modal");
                            window.print();
                            $("body").css("visibility", "visible");
                            $("body #container").css("display", "block");
                            $("#" + modalId).addClass("modal");
                            $(event.target).closest(".modal").attr("id", oldId);
                        } else {
                            window.print();
                        }
                    }
                }
                ]
        });
    });');
        // find the menu items only once for more efficiency
        $this->_trackAnswer  = $this->findMenuItem('track', 'answer');
        $this->_surveyAnswer = $this->findMenuItem('survey', 'answer');
        $this->_takeSurvey   = $this->findMenuItem('ask', 'take');
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
            $status = $this->creator->span($this->translate->_('Fill in'), array('class' => 'warning'));
            $status = '';
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
            //$tokenLink[] = $this->creator->br();
            $tokenLink[] = $tokenData['gsu_survey_name'];
            $tokenDiv[]  = $tokenLink;
        } else {
            $this->_missed++;
            $tokenDiv->appendAttrib('class', ' danger');
            $status      = $this->creator->span($this->translate->_('Missed'), array('class' => 'danger'));
            $status = '';
            $tokenLink   = $tokenDiv->a('#', $status);
            //$tokenLink[] = $this->creator->br();
            $tokenLink[] = $tokenData['gsu_survey_name'];
        }
        return $tokenDiv;
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        // Load the display dateformat
        $dateOptions       = \MUtil_Model_Bridge_FormBridge::getFixedOptions('date');
        $this->_dateFormat = $dateOptions['dateFormat'];

        $this->creator = \Gems_Html::init();
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
     * @return type
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

        $main = $this->creator->div(array('id'=>'wrapper' , 'renderClosingTag' => true));

        $main->div(array('id' => 'modalpopup', 'renderClosingTag' => true));

        $model = $this->getModel();
        $model->trackUsage();
        $items = array(
            'gto_id_respondent_track',
            'gto_valid_from',
            'gr2t_start_date',
            'gtr_track_name',
            'gr2t_track_info',
            'gto_id_token',
            'gto_round_description',
            'forgroup',
            'gsu_survey_name',
            'gto_completion_time',
            'gr2o_patient_nr',
            'gr2o_id_organization'
        );
        foreach ($items as $item)
        {
            $model->get($item);
        }

        $data            = $model->load(true, $this->_fixedSort);
        $lastDate        = null;
        $lastDescription = null;
        $doelgroep       = null;
        $today           = new \MUtil_Date();
        $today           = $today->get($this->_dateFormat);
        $progressDiv     = null;
        $respTrackId     = 0;

        // The normal loop
        foreach ($data as $row)
        {
            if ($respTrackId !== $row['gto_id_respondent_track']) {
                $lastDate    = null;
                $doelgroep   = null;
                //if ($respTrackId == 0) {
                //    $track = $main->div(array('class' => 'panel panel-default traject active'));
                //} else {
                    $track = $main->div(array('class' => 'panel panel-default traject'));
                //}
                $respTrackId = $row['gto_id_respondent_track'];
                $trackHeading = $track->div(array('class' => 'panel-heading', 'renderClosingTag' => true));
                $trackHeading->h3($row['gtr_track_name'], array('class'=>"panel-title"))->span(array('class'=>"fa fa-chevron-down fa-fw"));

                $editLink = \MUtil_Html::create('span', array('class' => 'fa fa-pencil', 'renderClosingTag' => true));

                    $editTrackContainer = \MUtil_Html::create('div', array('class' => 'editIcon'));
                     $link = $this->createMenuLink(
                        array(
                            'gr2t_id_respondent_track' => $row['gto_id_respondent_track'],
                            'gr2o_patient_nr' => $row['gr2o_patient_nr'],
                            'gr2o_id_organization' => $row['gr2o_id_organization'],
                            'can_edit' => 1
                        ),
                        'track',
                        'edit-track',   // Somehow edit track won't show up
                        $editLink
                    );
                     $link->addCancelBubble();
                     $link->setAttrib('onClick', 'event.cancelBubble = true;');
                    $editTrackContainer[] = $link;
                    $trackHeading[]   = $editTrackContainer;

                $trackHeading->div($row['gr2t_track_info'], array('renderClosingTag' => true));
                $trackHeading->div($this->_('Start date') . ': ' . $row['gr2t_start_date']->get($this->_dateFormat), array('renderClosingTag' => true));

                $container        = $track->div(array('class' => 'panel-body', 'renderClosingTag' => true));
                $cva              = $container->div(array('class' => 'objecten', 'renderClosingTag' => true));
            }
            $date = $row['gto_valid_from'];
            if ($date instanceof \Zend_Date) {
                $date = $date->get($this->_dateFormat);
            } else {
                continue;
            }

            $description = $row['gto_round_description'];
            if ($date !== $lastDate || $lastDescription !== $description) {
                $lastDescription = $description;
                $progressDiv     = $this->finishGroup($progressDiv);
                $lastDate        = $date;
                $class           = 'object';
                if ($date == $today) {
                    $class .= ' today';
                }
                $day       = $cva->div(array('class' => $class, 'renderClosingTag' => true));
                $day->h4(ucfirst($row['gto_round_description']));
                $day->h5($date);

                $doelgroep = null;
            }

            if ($doelgroep !== $row['forgroup']) {
                $progressDiv  = $this->finishGroup($progressDiv);
                $doelgroep    = $row['forgroup'];
                $doelgroepDiv = $day->div(array('class' => 'actor', 'renderClosingTag' => true));
                //$progressDiv  = $doelgroepDiv->div(array('class' => 'progress'));
                $minIcon = \MUtil_Html::create('span',array('class' => 'fa fa-plus-square', 'renderClosingTag' => true));
                $title = $doelgroepDiv->h5(array($minIcon, $doelgroep));
                $progressDiv  = $doelgroepDiv->div(array('class' => 'zplegenda', 'renderClosingTag' => true));
                $tokenDiv = $doelgroepDiv->div(array('class' => 'zpitems', 'renderClosingTag' => true));
            }

            $tokenDiv[] = $this->addToken($row);
        }
        $progressDiv = $this->finishGroup($progressDiv);

        return $main;
    }

    protected function finishGroup($progressDiv)
    {
        $total = $this->_completed + $this->_open + $this->_missed;
        if (!is_null($progressDiv)) {
            if (! $this->_completed == 0) {
                $progressDiv->div(array('class' => 'success'))->append($this->_completed);
            }
            if (! $this->_open == 0) {
                $progressDiv->div(array('class' => 'warning'))->append($this->_open);
            }
            if (! $this->_missed == 0) {
                $progressDiv->div(array('class' => 'danger'))->append($this->_missed); //->setAttrib('style', sprintf('width: %s%%;', $this->_missed / $total * 100));
            }
        }
        $this->_completed = 0;
        $this->_open      = 0;
        $this->_missed    = 0;

        return;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model) {
        $model->setFilter($this->_fixedFilter);
        $filter['gto_id_respondent'] = $this->respondent->getId();
        if (is_array($this->forOtherOrgs)) {
            $filter['gto_id_organization'] = $this->forOtherOrgs;
        } elseif (true !== $this->forOtherOrgs) {
            $filter['gto_id_organization'] = $this->respondent->getOrganizationId();
        }

        // Filter for valid track reception codes
        $filter[]              = 'gr2t_reception_code IN (SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_success = 1)';
        $filter['grc_success'] = 1;
        // Active round
        // or
        // no round
        // or
        // token is success and completed
        $filter[] = 'gro_active = 1 OR gro_active IS NULL OR (grc_success=1 AND gto_completion_time IS NOT NULL)';
        $filter['gsu_active']  = 1;

        /* if ($tabFilter = $this->model->getMeta('tab_filter')) {
          $model->addFilter($tabFilter);
          } */

        $model->addFilter($filter);

        // \MUtil_Echo::track($model->getFilter());
        //$this->processSortOnly($model);
    }

}