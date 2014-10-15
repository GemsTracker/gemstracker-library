<?php

/**
 * Description of TrafficLightTokenSnippet
 *
 * Show the track in a different way, ordered by round and group showing
 * traffic light color indicating the status of a token and uses inline
 * answer display.
 *
 * @package    Gems
 * @subpackage Gems
 * @author     175780
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */
class Gems_Snippets_Respondent_TrafficLightTokenSnippet extends Gems_Snippets_RespondentTokenSnippet {

    public $browse = false;

    /**
     * @var MUtil_Html_Creator
     */
    public $creator         = null;
    protected $_fixedSort   = array(
        'gr2t_start_date'         => SORT_DESC,
        'gto_id_respondent_track' => SORT_DESC,
        'gto_valid_from'          => SORT_ASC,
        'gto_round_description'   => SORT_ASC,
        'forgroup'                => SORT_ASC
    );
    protected $_fixedFilter = array(
        'gto_valid_from <= NOW()'
    );
    protected $_completed   = 0;
    protected $_open        = 0;
    protected $_missed      = 0;

    /**
     * The display format for the date
     * 
     * @var string
     */
    protected $_dateFormat;

    /**
     * 
     * @var Gems_Menu_SubMenuItem
     */
    protected $_surveyAnswer;

    /**
     * 
     * @var Gems_Menu_SubMenuItem
     */
    protected $_trackAnswer;

    /**
     * 
     * @var Gems_Menu_SubMenuItem
     */
    protected $_takeSurvey;

    /**
     * Initialize the view
     *
     * Make sure the needed javascript is loaded
     *
     * @param Zend_View $view
     */
    protected function _initView($view) {
        $baseUrl = GemsEscort::getInstance()->basepath->getBasePath();

        // Make sure we can use jQuery
        ZendX_JQuery::enableView($view);

        // Now add the scrollTo plugin so we can scroll to today
        $view->headScript()->appendFile($baseUrl . '/gems/js/jquery.scrollTo.min.js');

        /*
         * And add some initialization:
         *  - Hide all tokens initially (accessability, when no javascript they should be visible)
         *  - If there is a day labeled today, scroll to it (prevents errors when not visible)
         */
        $view->headScript()->appendScript('
$(document).ready(function() {
    $(".doelgroep").click(function(){
        element = $(this).children(".ui-icon").first();
        if ( element.hasClass("ui-icon-triangle-1-e") ) {
            element.addClass("ui-icon-triangle-1-s" );
            element.removeClass("ui-icon-triangle-1-e" );
        } else {
            element.addClass("ui-icon-triangle-1-e" );
            element.removeClass("ui-icon-triangle-1-s" );
        };
        $(this).children(".progress").toggle();
        $(this).children(".token").toggle();
    });

$(".trackheader").click(function(){
        $(this).next().toggle();
        if($(this).next().is(":visible")) {
            // Scroll to today
            $(this).next().find(".day.today").each(function(){
                // Open current day
                $(this).children(".doelgroep").each(function(){if ( $(this).find(".progress").is(":visible") ) { $(this).click(); }});
                // Scroll to today
                $(this).parent().parent().scrollTo($(this),0, { offset: $(this).width()-$(this).parent().parent().width()} );    /* today is rightmost block */
            });
        }
    });

    $(".doelgroep").children(".token").toggle(false);

    $(".trackheader").click();
    $(".trackheader").first().click();

    // Extends the dialog widget with a new option.
    $.widget("app.dialog", $.ui.dialog, {

        options: {
            iconButtons: []
        },

        _create: function() {

            // Call the default widget constructor.
            this._super();

            // The dialog titlebar is the button container.
            var $titlebar = this.uiDialog.find( ".ui-dialog-titlebar" );

            // Iterate over the iconButtons array, which defaults to
            // and empty array, in which case, nothing happens.
            $.each( this.options.iconButtons, function( i, v ) {

                // Finds the last button added. This is actually the
                // left-most button.
                var $button = $( "<a/>" ).text( this.text ),
                    right = $titlebar.find( "[role=\'button\']:last" )
                                     .css( "right" );

                // Creates the button widget, adding it to the titlebar.
                $button.button( { icons: { primary: this.icon }, text: false } )
                       .addClass( "ui-dialog-titlebar-close" )
                       .removeClass( "ui-button-icon-only" )
                       .removeClass( "ui-state-default" )
                       .css( "right", ( parseInt( right ) + 22) + "px" )
                       .click( this.click )
                       .appendTo( $titlebar );

            });

        }

    });

    $("a.actionlink[target=\'inline\']").click(function(e){
        e.preventDefault();
        // Now open a new div, not #menu and bring it to the front
        // Add a close button to it, maybe the available tooltip can help here
        $("div#modalpopup").html("<div class=\'loading\'></div>"); // Make sure we show no old information
        $("div#modalpopup").load($(this).attr(\'href\'));
        $("div#modalpopup").dialog({
            modal: true,
            width: 500,
            position:{ my: "left top", at: "left top", of: "#main" },
            iconButtons: [
                {
                    text: "Print",
                    icon: "ui-icon-print",
                    click: function( e ) {
                        $(document.body).addClass("print");
                        $("div#modalpopup").addClass( "printable" );
                        window.print();
                    }
                }
                ],
            close: function( event, ui ) {
                $(document.body).removeClass("print");
            }
        });
    });
});');
        // find the menu items only once for more efficiency
        $this->_trackAnswer  = $this->findMenuItem('track', 'answer');
        $this->_surveyAnswer = $this->findMenuItem('survey', 'answer');
        $this->_takeSurvey   = $this->findMenuItem('ask', 'take');
    }

    /**
     * Copied from Gems_Token, to save overhead of loading a token just for this check
     * 
     * @param array $tokenData
     * @return boolean
     */
    protected function _isCompleted($tokenData) {
        return isset($tokenData['gto_completion_time']) && $tokenData['gto_completion_time'];
    }

    public function addToken($tokenData) {
        // We add all data we need so no database calls needed to load the token
        $tokenDiv = $this->creator->div(array('class' => 'token', 'renderClosingTag' => true));

        $tokenLink = null;

        if ($this->_isCompleted($tokenData)) {
            $status = $this->creator->span($this->translate->_('Answered'), array('class' => 'answered'));
            if ($tokenData['gtr_track_type'] == 'T') {
                $tokenLink = $this->createMenuLink($tokenData, 'track', 'answer', $status, $this->_trackAnswer);
            } else {
                $tokenLink = $this->createMenuLink($tokenData, 'survey', 'answer', $status, $this->_surveyAnswer);
            }
        } else {
            $status    = $this->creator->span($this->translate->_('Fill in'), array('class' => 'open'));
            $tokenLink = $this->createMenuLink($tokenData, 'ask', 'take', $status, $this->_takeSurvey);
        }

        if (!empty($tokenLink)) {
            if ($this->_isCompleted($tokenData)) {
                $this->_completed++;
                $tokenDiv->appendAttrib('class', ' answered');
                $tokenLink->target = 'inline';
            } else {
                $this->_open++;
                $tokenDiv->appendAttrib('class', ' open');
                $tokenLink->target = $tokenData['gto_id_token'];
            }
            $tokenLink[] = $this->creator->br();
            $tokenLink[] = $tokenData['gsu_survey_name'];
            $tokenDiv[]  = $tokenLink;
        } else {
            $this->_missed++;
            $tokenDiv->appendAttrib('class', ' missed');
            $status      = $this->creator->span($this->translate->_('Missed'), array('class' => 'missed'));
            $tokenLink   = $tokenDiv->a('#', $status);
            $tokenLink->appendAttrib('class', ' actionlink');
            $tokenLink[] = $this->creator->br();
            $tokenLink[] = $tokenData['gsu_survey_name'];
        }
        return $tokenDiv;
    }

    public function afterRegistry() {
        parent::afterRegistry();

        // Load the display dateformat
        $dateOptions       = MUtil_Model_Bridge_FormBridge::getFixedOptions('date');
        $this->_dateFormat = $dateOptions['dateFormat'];

        $this->creator = Gems_Html::init();
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
    public function createMenuLink($parameterSource, $controller, $action = 'index', $label = null, $menuItem = null) {
        if (!is_null($menuItem) || $menuItem = $this->findMenuItem($controller, $action)) {
            return $menuItem->toActionLinkLower($this->request, $parameterSource, $label);
        }
    }

    public function createModel() {
        $model = parent::createModel();

        $model->addColumn('gems__groups.ggp_name', 'forgroup');

        return $model;
    }

    public function getHtmlOutput(Zend_View_Abstract $view) {
        $this->_initView($view);

        $main = $this->creator->div(array('class' => 'wrapper', 'renderClosingTag' => true));
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
            'gtr_track_type',
            'gsu_survey_name',
            'gto_completion_time'
        );
        foreach ($items as $item)
        {
            $model->get($item);
        }

        $data            = $model->load(true, $this->_fixedSort);
        $lastDate        = null;
        $lastDescription = null;
        $doelgroep       = null;
        $today           = new MUtil_Date();
        $today           = $today->get($this->_dateFormat);
        $progressDiv     = null;
        $respTrackId     = 0;

        // The normal loop
        foreach ($data as $row)
        {
            if ($respTrackId !== $row['gto_id_respondent_track']) {
                $lastDate    = null;
                $doelgroep   = null;
                $respTrackId = $row['gto_id_respondent_track'];
                $track       = $main->div(array('class' => 'trackheader'));
                $track->div($row['gtr_track_name'], array('class' => 'tracktitle', 'renderClosingTag' => true));
                $track->div($row['gr2t_track_info'], array('class' => 'trackinfo', 'renderClosingTag' => true));
                $track->div($row['gr2t_start_date']->get($this->_dateFormat), array('class' => 'trackdate', 'renderClosingTag' => true));
                $container   = $main->div(array('class' => 'scrollContainer', 'renderClosingTag' => true));
                $cva         = $container->div(array('class' => 'cvacontainer', 'renderClosingTag' => true));
            }
            $date = $row['gto_valid_from'];
            if ($date instanceof Zend_Date) {
                $date = $date->get($this->_dateFormat);
            } else {
                continue;
            }

            $description = $row['gto_round_description'];
            if ($date !== $lastDate || $lastDescription !== $description) {
                $lastDescription = $description;
                $progressDiv     = $this->finishGroup($progressDiv);
                $lastDate        = $date;
                $class           = 'day';
                if ($date == $today) {
                    $class .= ' today';
                }
                $day       = $cva->div(array('class' => $class));
                $dayheader = $day->div(array('class' => 'dayheader'));

                $dayheader[] = $this->creator->div(ucfirst($row['gto_round_description']), array('class' => 'roundDescription', 'renderClosingTag' => true));
                $dayheader[] = $date;

                $doelgroep = null;
            }

            if ($doelgroep !== $row['forgroup']) {
                $progressDiv  = $this->finishGroup($progressDiv);
                $doelgroep    = $row['forgroup'];
                $doelgroepDiv = $day->div(array('class' => 'doelgroep'));
                //$progressDiv  = $doelgroepDiv->div(array('class' => 'progress'));
                $doelgroepDiv->span('.', array('class' => 'ui-icon ui-button ui-icon-triangle-1-e'));
                $doelgroepDiv->span($doelgroep, array('class' => 'title'));
                $progressDiv  = $doelgroepDiv->div(array('class' => 'progress'));
            }

            $doelgroepDiv[] = $this->addToken($row);
        }
        $progressDiv = $this->finishGroup($progressDiv);

        return $main;
    }

    protected function finishGroup($progressDiv) {
        $total = $this->_completed + $this->_open + $this->_missed;
        if (!is_null($progressDiv)) {
            $progressDiv->div(array('class' => 'answered'))->append($this->_completed)->setAttrib('style', sprintf('width: %s%%;', $this->_completed / $total * 100));
            $progressDiv->div(array('class' => 'open'))->append($this->_open)->setAttrib('style', sprintf('width: %s%%;', $this->_open / $total * 100));
            $progressDiv->div(array('class' => 'missed'))->append($this->_missed)->setAttrib('style', sprintf('width: %s%%;', $this->_missed / $total * 100));
        }
        $this->_completed = 0;
        $this->_open      = 0;
        $this->_missed    = 0;

        return;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(MUtil_Model_ModelAbstract $model) {
        $model->setFilter($this->_fixedFilter);
        $filter['gto_id_respondent'] = $this->respondentData['grs_id_user'];
        if (is_array($this->forOtherOrgs)) {
            $filter['gto_id_organization'] = $this->forOtherOrgs;
        } elseif (true !== $this->forOtherOrgs) {
            $filter['gto_id_organization'] = $this->respondentData['gr2o_id_organization'];
        }

        // Filter for valid track reception codes
        $filter[]              = 'gr2t_reception_code IN (SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_success = 1)';
        $filter['grc_success'] = 1;
        $filter['gro_active']  = 1;
        $filter['gsu_active']  = 1;

        /* if ($tabFilter = $this->model->getMeta('tab_filter')) {
          $model->addFilter($tabFilter);
          } */

        $model->addFilter($filter);

        // MUtil_Echo::track($model->getFilter());
        //$this->processSortOnly($model);
    }

}