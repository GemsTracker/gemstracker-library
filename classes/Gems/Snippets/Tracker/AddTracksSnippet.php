<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

/**
 * Displays a toolbox of drop down UL's to assign tracks / surveys to a patient.
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see \MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class AddTracksSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Zend_Locale
     */
    public $locale;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Optional: $request or $tokenData must be set
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * When using bootstrap and more than this number of items the dropdowns will
     * support scrolling.
     *
     * @var int
     */
    public $scrollTreshold = 10;

    /**
     * Switch to set display of respondent dropdown on or off
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public $showForRespondents = true;

    /**
     * Switch to set display of staff dropdown on or off
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public $showForStaff = true;


    /**
     * Switch to set display of track dropdown on or off
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public $showForTracks = true;

    /**
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public $showTitle = true;

    protected function _getTracks($trackType, $pageRef, $trackTypeDescription)
    {
        switch ($trackType) {
            case 'tracks':
                $action = 'create';
                break;

            case 'respondents':
            case 'staff':
                $action = 'insert';
                break;
        }
        $orgId   = intval($this->request->getParam(\MUtil_Model::REQUEST_ID2));
        $cacheId = strtr(__CLASS__ . '_' . $trackType . '_' . $orgId, '\\/' , '__');
        if ($this->project->translateDatabaseFields() && $this->project->getLocaleDefault() != $this->locale->getLanguage()) {
            $cacheId .= '_' . $this->locale->getLanguage();
        }

        $tracks  = $this->cache->load($cacheId);

        if (! $tracks) {
            switch ($trackType) {
                case 'tracks':
                    $select = $this->db->select();
                    $select->from('gems__tracks', ['gtr_id_track', 'gtr_track_name'])
                        ->where(new \Zend_Db_Expr('gtr_date_start < CURRENT_TIMESTAMP'))
                        ->where(new \Zend_Db_Expr('(gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP)'))
                        ->where('gtr_active = 1')
                        ->where('gtr_organizations LIKE \'%|'.$orgId.'|%\'')
                        ->order('gtr_track_name');

                    break;

                case 'respondents':
                    $select = $this->db->select();
                    $select->from('gems__surveys', ['gsu_id_survey', 'gsu_survey_name'])
                        ->join('gems__groups', 'gsu_id_primary_group = ggp_id_group', [])
                        ->where('gsu_surveyor_active = 1')
                        ->where('gsu_active = 1')
                        ->where('ggp_group_active = 1')
                        ->where('ggp_respondent_members = 1')
                        ->where('gsu_insertable = 1')
                        ->where('gsu_insert_organizations LIKE \'%|'.$orgId.'|%\'')
                        ->order('gsu_survey_name');
                    break;

                case 'staff':
                    $select = $this->db->select();
                    $select->from('gems__surveys', ['gsu_id_survey', 'gsu_survey_name'])
                        ->join('gems__groups', 'gsu_id_primary_group = ggp_id_group', [])
                        ->where('gsu_surveyor_active = 1')
                        ->where('gsu_active = 1')
                        ->where('ggp_group_active = 1')
                        ->where('ggp_staff_members = 1')
                        ->where('gsu_insertable = 1')
                        ->where('gsu_insert_organizations LIKE \'%|'.$orgId.'|%\'')
                        ->order('gsu_survey_name');
                    break;
            }

            if ($this->project->translateDatabaseFields()) {
                $dbTranslations = $this->loader->getDbTranslations();
                $tracks = $dbTranslations->translatePairsFromSelect($select);
            } else {
                $tracks = $this->db->fetchPairs($select);
            }

            $this->cache->save($tracks, $cacheId, array('surveys', 'tracks'));
        }

        if ($trackType != 'tracks') {
            $div = \MUtil_Html::create()->div(array('class' => 'btn-group'));
        } else {
            $div = \MUtil_Html::create()->div(array('class' => 'toolbox btn-group'));
        }

        $menuIndex  = $this->menu->findController('track', 'index');

        if ($tracks) {
            $menuCreate = $this->menu->findController('track', $action);

            if (! $menuCreate->isAllowed()) {
                return null;
            }

            if (\MUtil_Bootstrap::enabled()) {
                $div->button($trackTypeDescription,
                    array('class' => 'toolanchor btn', 'data-toggle' => 'dropdown', 'type' => 'button'));
                $dropdownButton = $div->button(array(
                    'class' => 'btn dropdown-toggle',
                    'data-toggle' => 'dropdown',
                    'type' => 'button',
                    ));
                $dropdownButton->span(array('class' => 'caret', 'renderClosingTag' => true));
            } else {
                $div->a($menuIndex->toHRefAttribute($this->request), $trackTypeDescription, array('class' => 'toolanchor'));
            }

            $data   = new \MUtil_Lazy_RepeatableByKeyValue($tracks);

            if ($trackType == 'tracks') {
                $menuView   = $this->menu->findController('track', 'view');
                $params = array('gtr_id_track' => $data->key);
            } else {
                $menuView   = $this->menu->findController('track', 'view-survey');
                $params = array('gsu_id_survey' => $data->key);
            }

            if (\MUtil_Bootstrap::enabled()) {
                if (count($tracks) > $this->scrollTreshold) {
                    // Add a header and scroll class so we keep rounded corners
                    $top  = $div->ul(array('class' => 'dropdown-menu', 'role' => 'menu'));
                    $link = $top->li(array('class' => 'disabled'))->a('#');
                    $link->i(array('class' => 'fa fa-chevron-down fa-fw pull-left', 'renderClosingTag' => true));
                    $link->i(array('class' => 'fa fa-chevron-down fa-fw pull-right', 'renderClosingTag' => true));
                    // Add extra scroll-menu class
                    $li   = $top->li()->ul(array('class' => 'dropdown-menu scroll-menu', 'role' => 'menu'), $data)->li();
                } else {
                    $li = $div->ul(array('class' => 'dropdown-menu', 'role' => 'menu'), $data)->li();
                }

                $link = $li->a($menuView->toHRefAttribute($this->request, $params), array('class' => 'rightFloat info'));
                $link->i(array('class' => 'fa fa-info-circle'))->raw('&nbsp;');

                if (count($tracks) > $this->scrollTreshold) {
                    // Add a footer so we keep rounded corners
                    $link = $top->li(array('class' => 'disabled'))->a('#');
                    $link->i(array('class' => 'fa fa-chevron-up fa-fw pull-left', 'renderClosingTag' => true));
                    $link->i(array('class' => 'fa fa-chevron-up fa-fw pull-right', 'renderClosingTag' => true));
                }
            } else {
                $li = $div->ul($data)->li();
                $li->a($menuView->toHRefAttribute($this->request, $params), array('class' => 'rightFloat'))
                    ->img(array('src' => 'info.png', 'width' => 12, 'height' => 12, 'alt' => $this->_('info')));
            }

            $toolboxRowAttributes = array('class' => 'add');
            $li->a($menuCreate->toHRefAttribute($this->request, $params),
                    $data->value,
                    $toolboxRowAttributes);

        } else {
            if (\MUtil_Bootstrap::enabled()) {
                $div->button($trackTypeDescription,
                    array('class' => 'toolanchor btn disabled', 'data-toggle' => 'dropdown', 'type' => 'button'));
                $dropdownButton = $div->button(array(
                    'class' => 'disabled btn dropdown-toggle',
                    'data-toggle' => 'dropdown',
                    'type' => 'button',
                    ));
                $dropdownButton->span(array('class' => 'caret', 'renderClosingTag' => true));
                $options = array('class' => 'dropdown-menu disabled', 'role' => 'menu');
            } else {
                $div->a($menuIndex->toHRefAttribute($this->request),
                        $trackTypeDescription,
                        array('class' => 'toolanchor disabled')
                        );

                $options = array('class' => 'disabled');
            }

            $div->ul($this->_('None available'), $options);
        }

        return $div;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->showForRespondents && is_bool($this->showForRespondents)) {
            $this->showForRespondents = $this->_('Respondents');
        }
        if ($this->showForStaff && is_bool($this->showForStaff)) {
            $this->showForStaff = $this->_('Staff');
        }
        if ($this->showForTracks && is_bool($this->showForTracks)) {
            $this->showForTracks = $this->_('Tracks');
        }
        if ($this->showTitle && is_bool($this->showTitle)) {
            $this->showTitle = $this->_('Add');
        }
    }

    /**
     * Allow manual assignment of surveys/tracks to a patient
     *
     * If project uses the \Gems_Project_Tracks_MultiTracksInterface, show a track drowpdown
     * If project uses the \Gems_Project_Tracks_StandAloneSurveysInterface, show a survey
     * drowpdown for both staff and patient
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $pageRef = array(\MUtil_Model::REQUEST_ID => $this->request->getParam(\MUtil_Model::REQUEST_ID));
        $output  = false;

        $addToLists = \MUtil_Html::create()->div(array('class' => 'tooldock'));
        if ($this->showTitle) {
            $addToLists->strong($this->showTitle);
        }
        if ($this->showForTracks) {
            $dropdown = $this->_getTracks('tracks', $pageRef, $this->showForTracks);
            if ($dropdown) {
                $addToLists[] = $dropdown;
                $output       = true;
            }
        }
        if ($this->showForRespondents || $this->showForStaff) {
            $div = \MUtil_Html::create()->div(array('class' => 'toolbox btn-group'));
            $div->button($this->_('Surveys for'), array('class' => 'toolanchor btn', 'type' => 'button'));

            if ($this->showForRespondents) {
                $dropdown = $this->_getTracks('respondents', $pageRef, $this->showForRespondents);
                if ($dropdown) {
                    $div[]  = $dropdown;
                    $output = true;
                }
            }
            if ($this->showForStaff) {
                $dropdown = $this->_getTracks('staff', $pageRef, $this->showForStaff);
                if ($dropdown) {
                    $div[]  = $dropdown;
                    $output = true;
                }
            }
            $addToLists[] = $div;
        }

        if ($output) {
            return $addToLists;
        }
    }
}
