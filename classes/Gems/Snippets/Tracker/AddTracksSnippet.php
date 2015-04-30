<?php
/**
 * Copyright (c) 2011, Erasmus MC
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
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AddTracksSnippet.php 2308 2014-12-10 14:50:33Z mennodekker $
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
     *
     * @var \Gems_Menu
     */
    protected $menu;

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
        $tracks  = $this->cache->load($cacheId);

        if (true || ! $tracks) {
            switch ($trackType) {
                case 'tracks':
                    $sql = "SELECT gtr_id_track, gtr_track_name
                        FROM gems__tracks
                        WHERE gtr_date_start < CURRENT_TIMESTAMP AND
                            (gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP) AND
                            gtr_active = 1 AND
                            gtr_organizations LIKE '%|$orgId|%'
                         ORDER BY gtr_track_name";
                    break;

                case 'respondents':
                    $sql = "SELECT gsu_id_survey, gsu_survey_name
                        FROM gems__surveys INNER JOIN gems__groups ON gsu_id_primary_group = ggp_id_group
                        WHERE gsu_surveyor_active = 1 AND
                            gsu_active = 1 AND
                            ggp_group_active = 1 AND
                            ggp_respondent_members = 1 AND
                            gsu_insert_organizations LIKE '%|$orgId|%'
                        ORDER BY gsu_survey_name";
                    break;

                case 'staff':
                    $sql = "SELECT gsu_id_survey, gsu_survey_name
                        FROM gems__surveys INNER JOIN gems__groups ON gsu_id_primary_group = ggp_id_group
                        WHERE gsu_surveyor_active = 1 AND
                            gsu_active = 1 AND
                            ggp_group_active = 1 AND
                            ggp_staff_members = 1 AND
                            gsu_insert_organizations LIKE '%|$orgId|%'
                        ORDER BY gsu_survey_name";
                    break;
            }
            $tracks = $this->db->fetchPairs($sql);

            $this->cache->save($tracks, $cacheId, array('surveys', 'tracks'));
        }

        $div = \MUtil_Html::create()->div(array('class' => 'toolbox btn-group'));

        $menuIndex  = $this->menu->findController('track', 'index');

        if ($tracks) {
            $menuView   = $this->menu->findController('track', 'view');
            $menuCreate = $this->menu->findController('track', $action);

            if (! $menuCreate->isAllowed()) {
                return null;
            }

            if (\MUtil_Bootstrap::enabled()) {
                $div->button($menuIndex->toHRefAttribute($this->request), $trackTypeDescription,
                    array('class' => 'toolanchor btn btn-primary', 'data-toggle' => 'dropdown', 'type' => 'button'));
                $dropdownButton = $div->button(array('class' => 'btn btn-primary dropdown-toggle', 'data-toggle' => 'dropdown', 'type' => 'button'));
                $dropdownButton->span(array('class' => 'caret'));
            } else {
                $div->a($menuIndex->toHRefAttribute($this->request), $trackTypeDescription, array('class' => 'toolanchor'));
            }

            $data   = new \MUtil_Lazy_RepeatableByKeyValue($tracks);
            $params = array('gtr_id_track' => $data->key, 'gsu_id_survey' => $data->key);

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
                $div->button($menuIndex->toHRefAttribute($this->request),                $trackTypeDescription,
                    array('class' => 'toolanchor btn btn-primary disabled', 'data-toggle' => 'dropdown', 'type' => 'button'));
                $dropdownButton = $div->button(array('class' => 'btn btn-primary disabled dropdown-toggle', 'data-toggle' => 'dropdown', 'type' => 'button'));
                $dropdownButton->span(array('class' => 'caret'));
                $options = array('class' => 'dropdown-menu disabled', 'role' => 'menu');
            } else {
                $div->a($menuIndex->toHRefAttribute($this->request),
                        $trackTypeDescription,
                        array('class' => 'toolanchor disabled'));

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
            $this->showForRespondents = $this->_('by Respondents');
        }
        if ($this->showForStaff && is_bool($this->showForStaff)) {
            $this->showForStaff = $this->_('by Staff');
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
        if ($this->showForRespondents) {
            $dropdown = $this->_getTracks('respondents', $pageRef, $this->showForRespondents);
            if ($dropdown) {
                $addToLists[] = $dropdown;
                $output       = true;
            }
        }
        if ($this->showForStaff) {
            $dropdown = $this->_getTracks('staff', $pageRef, $this->showForStaff);
            if ($dropdown) {
                $addToLists[] = $dropdown;
                $output       = true;
            }
        }
        if ($output) {
            return $addToLists;
        }
    }
}