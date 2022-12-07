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
use Gems\Html;
use Gems\Loader;
use Gems\Locale\Locale;
use Gems\MenuNew\RouteHelper;
use Gems\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Late\RepeatableByKeyValue;
use Zalt\Snippets\TranslatableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

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
class AddTracksSnippet extends TranslatableSnippetAbstract
{
    /**
     * When using bootstrap and more than this number of items the dropdowns will
     * support scrolling.
     *
     * @var int
     */
    public int $scrollTreshold = 10;

    /**
     * Switch to set display of respondent dropdown on or off
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public bool|string $showForRespondents = true;

    /**
     * Switch to set display of staff dropdown on or off
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public bool|string $showForStaff = true;


    /**
     * Switch to set display of track dropdown on or off
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public bool|string $showForTracks = true;

    /**
     *
     * @var mixed When string, string is used for display, when false, nothing is displayed
     */
    public bool|string $showTitle = true;

    public function __construct(
        SnippetOptions $snippetOptions,
        protected RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected HelperAdapter $cache, 
        protected \Zend_Db_Adapter_Abstract $db,
        protected Loader $loader,
        protected Locale $locale,
        protected RouteHelper $routeHelper 
    )
    {
        parent::__construct($snippetOptions, $this->requestInfo, $translate);
        
        $this->initTexts();
    }

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
        $params = $this->requestInfo->getRequestMatchedParams();
        if (isset($params[\MUtil\Model::REQUEST_ID2])) {
            $orgId = (int) $params[\MUtil\Model::REQUEST_ID2];
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

        $div = Html::create()->div(['class' => 'dropdown btn-group']);

        if ($tracks) {
            $params = $this->requestInfo->getRequestMatchedParams();

            $div->button($trackTypeDescription, [
                'class' => 'btn dropdown-toggle',
                'data-bs-toggle' => 'dropdown',
                'type' => 'button',
            ]);

            $data = new RepeatableByKeyValue($tracks);

            if ($trackType == 'tracks') {
                $params[Model::TRACK_ID] = $data->key;
                $menuViewUrl = $this->routeHelper->getRouteUrl('respondent.tracks.view', $params);
                $menuCreateUrl = $this->routeHelper->getRouteUrl('respondent.tracks.' . $action, $params);
            } else {
                $params[Model::SURVEY_ID] = $data->key;
                $menuViewUrl = $this->routeHelper->getRouteUrl('respondent.tracks.view-survey', $params);
                $menuCreateUrl = $this->routeHelper->getRouteUrl('respondent.tracks.' . $action, $params);
            }

            if (count($tracks) > $this->scrollTreshold) {
                // Add a header and scroll class so we keep rounded corners
                $top  = $div->ul(['class' => 'dropdown-menu']);
                $link = $top->li(['class' => 'disabled'])->a('#');
                $link->i(['class' => 'fa fa-chevron-down fa-fw pull-left', 'renderClosingTag' => true]);
                $link->i(['class' => 'fa fa-chevron-down fa-fw pull-right', 'renderClosingTag' => true]);
                // Add extra scroll-menu class
                $li   = $top->li()->ul(['class' => 'dropdown-menu scroll-menu', 'role' => 'menu'], $data)->li();
            } else {
                $li = $div->ul(['class' => 'dropdown-menu', 'role' => 'menu'], $data)->li();
            }

            if (count($tracks) > $this->scrollTreshold) {
                // Add a footer so we keep rounded corners
                $link = $top->li(['class' => 'disabled'])->a('#');
                $link->i(['class' => 'fa fa-chevron-up fa-fw pull-left', 'renderClosingTag' => true]);
                $link->i(['class' => 'fa fa-chevron-up fa-fw pull-right', 'renderClosingTag' => true]);
            }

            $toolboxRowAttributes = ['class' => 'dropdown-item'];
            $li->a($menuCreateUrl,
                $data->value,
                $toolboxRowAttributes);

        } else {
            $div->button($trackTypeDescription, [
                'class' => 'btn dropdown-toggle',
                'data-bs-toggle' => 'dropdown',
                'type' => 'button',
            ]);
        }

        return $div;
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
    public function getHtmlOutput()
    {
        $pageRef = null;
        $queryParams = $this->requestInfo->getRequestQueryParams();
        if (isset($queryParams[\MUtil\Model::REQUEST_ID])) {
            $pageRef = [\MUtil\Model::REQUEST_ID => $queryParams[\MUtil\Model::REQUEST_ID]];
        }

        $output  = false;

        $addToLists = Html::create()->div(['class' => 'track-buttons']);
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
            $div = Html::create()->div(['class' => 'btn-group']);
            $div->button($this->_('Surveys for'), ['class' => 'btn', 'type' => 'button', 'disabled' => true ]);

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
    
    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function initTexts()
    {
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
}
