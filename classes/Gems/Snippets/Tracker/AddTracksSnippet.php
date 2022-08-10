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

use Gems\Cache\HelperAdapter;
use Gems\Locale\Locale;
use Gems\MenuNew\RouteHelper;
use MUtil\Request\RequestInfo;

/**
 * Displays a toolbox of drop down UL's to assign tracks / surveys to a patient.
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see \MUtil\Registry\TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class AddTracksSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var HelperAdapter
     */
    protected $cache;

    /**
     * @var array
     */
    protected $config;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * @var Locale
     */
    public $locale;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     * @var RequestInfo
     */
    protected $requestInfo;

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

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
        $orgId = null;
        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[\MUtil\Model::REQUEST_ID2])) {
            $orgId = (int) $queryParams[\MUtil\Model::REQUEST_ID2];
        }

        $cacheId = strtr(__CLASS__ . '_' . $trackType . '_' . $orgId, '\\/' , '__');
        $translateDatabaseFields = false;
        if (isset($this->config['translations'], $this->config['translations']['databaseFields'], $this->config['locale'], $this->config['locale']['default']) &&
            $this->config['translations']['databaseFields'] && $this->config['locale']['default'] !==  $this->locale->getLanguage()
        ) {
            $cacheId .= '_' . $this->locale->getLanguage();
            $translateDatabaseFields = true;
        }

        $tracks  = $this->cache->getCacheItem($cacheId);

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

            if ($translateDatabaseFields) {
                $dbTranslations = $this->loader->getDbTranslations();
                $tracks = $dbTranslations->translatePairsFromSelect($select);
            } else {
                $tracks = $this->db->fetchPairs($select);
            }

            $this->cache->setCacheItem($cacheId, $tracks, ['surveys', 'tracks']);
        }

        if ($trackType != 'tracks') {
            $div = \MUtil\Html::create()->div(array('class' => 'btn-group'));
        } else {
            $div = \MUtil\Html::create()->div(array('class' => 'toolbox btn-group'));
        }

        if ($tracks) {
            $menuCreateUrl = $this->routeHelper->getRouteUrl('respondent.tracks.' . $action);

            if ($menuCreateUrl === null) {
                return null;
            }

            $div->button($trackTypeDescription,
                array('class' => 'toolanchor btn', 'data-toggle' => 'dropdown', 'type' => 'button'));
            $dropdownButton = $div->button(array(
                'class' => 'btn dropdown-toggle',
                'data-toggle' => 'dropdown',
                'type' => 'button',
                ));
            $dropdownButton->span(array('class' => 'caret', 'renderClosingTag' => true));

            $data   = new \MUtil\Lazy\RepeatableByKeyValue($tracks);

            if ($trackType == 'tracks') {
                $menuViewUrl = $this->routeHelper->getRouteUrl('respondent.tracks.view');
                $params = array('gtr_id_track' => $data->key);
            } else {
                $menuViewUrl = $this->routeHelper->getRouteUrl('respondent.tracks.view-survey');
                $params = array('gsu_id_survey' => $data->key);
            }

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

            $toolboxRowAttributes = array('class' => 'add');
            $li->a($menuCreate->toHRefAttribute($this->request, $params),
                    $data->value,
                    $toolboxRowAttributes);

        } else {
            $div->button($trackTypeDescription,
                array('class' => 'toolanchor btn disabled', 'data-toggle' => 'dropdown', 'type' => 'button'));
            $dropdownButton = $div->button(array(
                'class' => 'disabled btn dropdown-toggle',
                'data-toggle' => 'dropdown',
                'type' => 'button',
                ));
            $dropdownButton->span(array('class' => 'caret', 'renderClosingTag' => true));
            $options = array('class' => 'dropdown-menu disabled', 'role' => 'menu');

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
     * If project uses the \Gems\Project\Tracks\MultiTracksInterface, show a track drowpdown
     * If project uses the \Gems\Project\Tracks\StandAloneSurveysInterface, show a survey
     * drowpdown for both staff and patient
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $pageRef = null;
        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[\MUtil\Model::REQUEST_ID])) {
            $pageRef = [\MUtil\Model::REQUEST_ID => $queryParams[\MUtil\Model::REQUEST_ID]];
        }

        $output  = false;

        $addToLists = \MUtil\Html::create()->div(array('class' => 'tooldock'));
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
            $div = \MUtil\Html::create()->div(array('class' => 'toolbox btn-group'));
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
