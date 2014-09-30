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
class Gems_Default_ProjectTracksAction extends Gems_Controller_BrowseEditAction
{
    const TRACK_TYPE = 'T';

    public $filterStandard = array(
        'gtr_track_type' => self::TRACK_TYPE,
        'gtr_active' => 1,
        '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE) AND gtr_date_start <= CURRENT_DATE');

    public $sortKey = array('gtr_track_name' => SORT_ASC);


    protected function createModel($detailed, $action)
    {
        $translated = $this->util->getTranslated();

        $model = new MUtil_Model_TableModel('gems__tracks');
        //$model->resetOrder();

        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gtr_survey_rounds', 'label', $this->_('Survey #'));
        // $model->set('gtr_date_start',    'label', $this->_('From'),  'dateFormat', 'dd-MM-yyyy', 'tdClass', 'date');
        $model->set('gtr_date_start',    'label', $this->_('From'),  'dateFormat', $translated->formatDate, 'tdClass', 'date');
        $model->set('gtr_date_until',    'label', $this->_('Until'), 'dateFormat', $translated->formatDateForever, 'tdClass', 'date');

        return $model;
    }

    protected function getDataFilter(array $data)
    {
        $filter = parent::getDataFilter($data);

        $organization_id = $this->escort->getCurrentOrganization();
        $filter[] = "gtr_organizations LIKE '%|$organization_id|%'";

        return $filter;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Active tracks');
    }

    /*
    public function indexAction()
    {
        parent::indexAction();

        $user = $this->loader->getCurrentUser();
        foreach (array('X1X', 'adminijadIran', 'xx!2yy2z', 'admin2') as $password) {
            $this->addMessage($password);
            $this->addMessage($user->reportPasswordWeakness($password));
        }
    } // */

    public function questionsAction()
    {
        if ($sid = $this->_getParam(Gems_Model::SURVEY_ID)) {

            if ($title = $this->db->fetchOne("SELECT gsu_survey_name FROM gems__surveys WHERE gsu_id_survey = ?", $sid)) {
                $this->html->h3(sprintf($this->_('Questions in survey %s'), $title));

                $this->addSnippet('SurveyQuestionsSnippet', 'surveyId', $sid);

               if ($links = $this->createMenuLinks(10)) {
                    $this->html->buttonDiv($links);
                }
            } else {
                $this->addMessage(sprintf($this->_('Survey %s does not exist.'), $this->view->escape($sid)));
            }
        } else {
            $this->addMessage($this->_('Survey not specified.'));
        }
    }

    public function showAction()
    {
        $gtr_id_track = $this->_getIdParam();

        if ($useDetails = $this->addSnippet('TrackUsageTextDetailsSnippet', 'trackId', $gtr_id_track, 'showHeader', true)) {
            $this->addSnippet('TrackSurveyOverviewSnippet', 'trackData', $useDetails->getTrackData());
        } else {
            $this->addMessage(sprintf($this->_('Track %s does not exist.'), $this->view->escape($gtr_id_track)));
        }
    }
}
