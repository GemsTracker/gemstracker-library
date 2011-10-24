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
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_Default_MailJobAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var ArrayObject
     */
    public $project;


    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        $this->autofilterParameters['onEmpty'] = $this->_('No automatic mail jobs found...');

        parent::autofilterAction($resetMvc);
    }

    /**
     * Action for showing a create new item page
     */
    public function createAction()
    {
        $this->createEditParameters['formTitle'] = $this->_('New automatic mail job...');

        parent::createAction();
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $dbLookup   = $this->util->getDbLookup();
        $dbTracks   = $this->util->getTrackData();
        $translated = $this->util->getTranslated();
        $empty      = $translated->getEmptyDropdownArray();
        $unselected = array('' => '');

        $model = new MUtil_Model_TableModel('gems__mail_jobs');

        Gems_Model::setChangeFieldsByPrefix($model, 'gmj');

        $model->set('gmj_id_message',          'label', $this->_('Template'), 'multiOptions', $unselected + $dbLookup->getMailTemplates());
        $model->set('gmj_id_user_as',          'label', $this->_('By staff member'),
                'multiOptions', $unselected + $dbLookup->getActiveStaff(), 'default', $this->escort->getCurrentUserId(),
                'description', $this->_('Used for logging and possibly from address.'));
        $model->set('gmj_active',              'label', $this->_('Active'),
                'multiOptions', $translated->getYesNo(), 'elementClass', 'Checkbox', 'required', true,
                'description', $this->_('Job is only run when active.'));

        $fromMethods = $unselected + $this->getBulkMailFromOptions();
        $model->set('gmj_from_method',         'label', $this->_('From address used'), 'multiOptions', $fromMethods);
        if ($detailed) {
            $model->set('gmj_from_fixed',      'label', $this->_('From other'),
                    'description', sprintf($this->_("Only when '%s' is '%s'."), $model->get('gmj_from_method', 'label'), end($fromMethods)));
        }
        $model->set('gmj_process_method',      'label', $this->_('Processing Method'), 'multiOptions', $unselected + $translated->getBulkMailProcessOptions());
        $model->set('gmj_filter_mode',         'label', $this->_('Filter for'), 'multiOptions', $unselected + $this->getBulkMailFilterOptions());
        $model->set('gmj_filter_days_between', 'label', $this->_('Days between reminders'), 'validators[]', 'Digits');

        if ($detailed) {
            $model->set('gmj_id_organization', 'label', $this->_('Organization'), 'multiOptions', $empty + $dbLookup->getOrganizations());
            $model->set('gmj_id_track',        'label', $this->_('Track'), 'multiOptions', $empty + $dbTracks->getAllTracks());
            $model->set('gmj_id_survey',       'label', $this->_('Survey'), 'multiOptions', $empty + $dbTracks->getAllSurveys());
        }

        return $model;
    }

    /**
     * Action for showing a delete item page
     */
    public function deleteAction()
    {
        $this->deleteParameters['deleteQuestion'] = $this->_('Do you want to delete this mail job?');
        $this->deleteParameters['displayTitle']   = $this->deleteParameters['deleteQuestion'];

        parent::deleteAction();
    }

    /**
     * Action for showing a edit item page
     */
    public function editAction()
    {
        $this->createEditParameters['formTitle'] = $this->_('Edit automatic mail job');

        parent::editAction();
    }

    /**
     * The types of mail filters
     *
     * @return array
     */
    protected function getBulkMailFilterOptions()
    {
        return array(
            'N' => $this->_('First mail'),
            'R' => $this->_('Reminder'),
        );
    }

    /**
     * Options for from address use.
     *
     * @return array
     */
    protected function getBulkMailFromOptions()
    {
        $results['O'] = $this->_('Use organizational from address');

        if (isset($project->email['site']) && $project->email['site']) {
            $results['S'] = sprintf($this->_('Use site %s address'), $project->email['site']);
        }

        $results['U'] = $this->_("Use the 'By staff member' address");
        $results['F'] = $this->_('Other');

        return $results;
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $this->html->h3($this->_('Automatic mail jobs'));

        parent::indexAction();

        $this->html->pInfo($this->_('With automatic mail jobs and a cron job on the server, mails can be sent without manual user action.'));
    }

    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        $this->showParameters['displayTitle'] = $this->_('Automatic mail job details');

        parent::showAction();
    }
}
