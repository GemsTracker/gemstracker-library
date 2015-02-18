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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_SurveyAction extends Gems_Default_TrackActionAbstract
{
    /**
     * Use these snippets to show the content of a track
     *
     * @var mixed can be empty;
     */
    public $addTrackContentSnippets = 'Survey\\SurveyQuestionsSnippet';

    public $sortKey = array('gr2t_created' => SORT_DESC);

    public $trackType = 'S';

    /**
     * Overrules specific translations for this action
     *
     * @param  string             $text   Translation string
     * @param  string|Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        // Thanks to Potemkin adapter there is always a translate variable in MUtil_Controller_Action
        switch ($text) {
            case 'Add track':
                return $this->translate->getAdapter()->_('Add survey', $locale);

            case 'Add another %s track':
                return $this->translate->getAdapter()->_('Add another %s survey', $locale);

            case 'Adding the %s track to respondent %s':
                return $this->translate->getAdapter()->_('Adding the %s survey to respondent %s', $locale);

            case 'Available tracks':
                return $this->translate->getAdapter()->_('Available surveys', $locale);

            case 'No tracks found':
                return $this->translate->getAdapter()->_('No surveys found', $locale);

            case 'Overview of %s track for respondent %s: %s':
                return $this->translate->getAdapter()->_('Overview of %s survey for respondent %s: %s', $locale);

            case 'This track is currently not assigned to this respondent.':
                return $this->translate->getAdapter()->_('This survey has not been assigned to this respondent.', $locale);

            case 'Track %s does not exist.':
                return $this->translate->getAdapter()->_('Survey %s does not exist.', $locale);

            default:
                return parent::_($text, $locale);
        }
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_Bridge_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @rturn void
     */
    protected function addBrowseTableColumns(MUtil_Model_Bridge_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->gtr_track_type; // Data needed for buttons
        $bridge->gto_id_token;

        $bridge->tr()->class = $bridge->row_class;

        // Add show button if allowed
        if ($menuItem = $this->findAllowedMenuItem('show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        $bridge->addSortable('gsu_survey_name');
        $bridge->addSortable('gr2t_track_info');
        $bridge->addSortable('assigned_by');
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_completion_time');

        $links = array();
        // Add edit button if allowed
        if ($menuItem = $this->findAllowedMenuItem('edit')) {
            $links = $menuItem->toActionLinkLower($this->getRequest(), $bridge);
        }
        // Add answers button if allowed
        if ($menuItem = $this->findAllowedMenuItem('answer')) {
            $links = $menuItem->toActionLinkLower($this->getRequest(), $bridge);
        }

        if ($links) {
            $bridge->addItemLink($links);
        }

    }

    protected function addTrackUsage($respId, $orgId, $trackId, $baseUrl)
    {
        $data['grs_id_user']   = $this->db->fetchOne('SELECT gr2o_id_user FROM gems__respondent2org WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?', array($respId, $orgId));
        $data['gsu_id_survey'] = $this->db->fetchOne('SELECT gro_id_survey FROM gems__rounds WHERE gro_id_track = ?', $trackId);

        $result = $this->db->fetchOne('SELECT gto_id_token FROM gems__tokens WHERE gto_id_respondent = ? AND gto_id_survey = ?', $data);

        if ($result) {
            $this->html->h3(sprintf($this->_('Assignments of this survey to %s: %s'), $respId, $this->getRespondentName()));

            // MUtil_Echo::track($result);

            // Make sure request cache object is loaded.
            $this->getCachedRequestData();
            $this->addSnippet('BrowseSingleSurveyTokenSnippet', 'baseUrl', $baseUrl, 'filter', $data);
        }

        return (boolean) $result;
    }

    protected function createMenuLinks($includeLevel = 2, $parentLabel = true)
    {
        if ($includeLevel <= 10) {
            $includeLevel = 1;
        }

        $request = $this->getRequest();
        $links   = parent::createMenuLinks($includeLevel, $parentLabel);
        $parent  = reset($links);

        if (key($links) == 'survey.index') {
            $parent[0] = $this->_('Show surveys');
        }

        if (! isset($links['respondent.show'])) {
            // Add show patient button if allowed, otherwise show, again if allowed
            if ($menuItem = $this->menu->find(array('controller' => 'respondent', 'action' => 'show', 'allowed' => true))) {
                $links['respondent.show'] = $menuItem->toActionLink($request, $this, $this->_('Show respondent'));
            }
        }

        return $links;
    }

    protected function createModel($detailed, $action)
    {
        return $this->createTokenModel($detailed, $action);
    }

    public function createTrackModel($detailed, $action)
    {
        $model = new Gems_Model_JoinModel('tracks', 'gems__tracks');
        $model->addTable('gems__rounds',  array('gtr_id_track' => 'gro_id_track'));
        $model->addTable('gems__surveys', array('gro_id_survey' => 'gsu_id_survey'));
        $model->addTable('gems__groups',  array('gsu_id_primary_group' => 'ggp_id_group'));

        //$model->resetOrder();
        $model->set('gsu_survey_name', 'label', $this->_('Survey'));
        $model->set('ggp_name',        'label', $this->_('By'),    'elementClass', 'Exhibitor');
        $model->set('gtr_date_start',  'label', $this->_('From'),
                'dateFormat', 'dd-MM-yyyy',
                'tdClass', 'date',
                'formatFunction', $this->util->getTranslated()->formatDate);
        $model->set('gtr_date_until',  'label', $this->_('Until'),
                'dateFormat', 'dd-MM-yyyy',
                'tdClass', 'date',
                'formatFunction', $this->util->getTranslated()->formatDateNa);

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }

    public function getTopicTitle()
    {
        return sprintf($this->_('Surveys assigned to %s: %s'),
                $this->_getParam(MUtil_Model::REQUEST_ID1),
                $this->getRespondentName()
            );
    }
}
