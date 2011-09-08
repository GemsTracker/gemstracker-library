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
 * @version    $Id: ProjectSurveysAction.php 460 2011-08-31 16:17:26Z mjong $
 */

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_ProjectSurveysAction  extends Gems_Controller_BrowseEditAction
{
    const TRACK_TYPE = 'S';

    public $filterStandard = array(
        'gtr_track_type' => self::TRACK_TYPE,
        'gtr_active' => 1,
        '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE) AND gtr_date_start <= CURRENT_DATE');

    public $sortKey = array('gtr_track_name' => SORT_ASC);


    protected function createModel($detailed, $action)
    {
        $translated = $this->util->getTranslated();

        $model = new Gems_Model_JoinModel('tracks', 'gems__tracks');
        $model->addTable('gems__rounds',  array('gtr_id_track' => 'gro_id_track'));
        $model->addTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));
        $model->addTable('gems__groups',  array('gsu_id_primary_group' => 'ggp_id_group'));

        //$model->resetOrder();
        $model->set('gsu_survey_name', 'label', $this->_('Survey'));
        $model->set('ggp_name',        'label', $this->_('By'),    'elementClass', 'Exhibitor');
        $model->set('gtr_date_start',  'label', $this->_('From'),  'dateFormat', $translated->dateFormatString, 'tdClass', 'date');
        $model->set('gtr_date_until',  'label', $this->_('Until'), 'dateFormat', $translated->dateFormatString, 'tdClass', 'date',
            'formatFunction', $translated->formatDateForever);

        return $model;
    }

    protected function getDataFilter(array $data)
    {
        $filter = parent::getDataFilter($data);

        $organization_id = $this->escort->getCurrentOrganization();
        $filter[] = "gtr_organisations LIKE '%|$organization_id|%'";

        return $filter;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Active surveys');
    }

    public function showAction()
    {
        parent::showAction();

        $this->addSnippet('SurveyQuestionsSnippet', 'trackId', $this->_getIdParam());
    }
}
