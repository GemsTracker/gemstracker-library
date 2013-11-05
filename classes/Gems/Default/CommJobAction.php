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
 * @version    $Id: MailJobAction.php 972 2012-10-08 09:35:02Z mennodekker $
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
class Gems_Default_CommJobAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    public $project;

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

        $model = new MUtil_Model_TableModel('gems__comm_jobs');

        Gems_Model::setChangeFieldsByPrefix($model, 'gcj');

        $model->set('gcj_id_message',          'label', $this->_('Template'), 'multiOptions', $unselected + $dbLookup->getCommTemplates());
        $model->set('gcj_id_user_as',          'label', $this->_('By staff member'),
                'multiOptions', $unselected + $dbLookup->getActiveStaff(), 'default', $this->escort->getCurrentUserId(),
                'description', $this->_('Used for logging and possibly from address.'));
        $model->set('gcj_active',              'label', $this->_('Active'),
                'multiOptions', $translated->getYesNo(), 'elementClass', 'Checkbox', 'required', true,
                'description', $this->_('Job is only run when active.'));

        $fromMethods = $unselected + $this->getBulkMailFromOptions();
        $model->set('gcj_from_method',         'label', $this->_('From address used'), 'multiOptions', $fromMethods);
        if ($detailed) {
            $model->set('gcj_from_fixed',      'label', $this->_('From other'),
                    'description', sprintf($this->_("Only when '%s' is '%s'."), $model->get('gcj_from_method', 'label'), end($fromMethods)));
        }
        $model->set('gcj_process_method',      'label', $this->_('Processing Method'), 'default', 'O', 'multiOptions', $translated->getBulkMailProcessOptions());
        $model->set('gcj_filter_mode',         'label', $this->_('Filter for'), 'multiOptions', $unselected + $this->getBulkMailFilterOptions());
        
        // If you really want to see this information in the overview, uncomment for the shorter labels
        // $model->set('gcj_filter_days_between', 'label', $this->_('Interval'), 'validators[]', 'Digits');
        // $model->set('gcj_filter_max_reminders','label', $this->_('Max'), 'validators[]', 'Digits');
        
        $model->set('gcj_id_track',        'label', $this->_('Track'), 'multiOptions', $empty + $dbTracks->getAllTracks());
        $model->set('gcj_id_survey',       'label', $this->_('Survey'), 'multiOptions', $empty + $dbTracks->getAllSurveys());     

        if ($detailed) {
            $model->set('gcj_filter_days_between', 'label', $this->_('Days between reminders'), 'validators[]', 'Digits');
            $model->set('gcj_filter_max_reminders','label', $this->_('Maximum number of reminders'), 'validators[]', 'Digits');
            $model->set('gcj_id_organization', 'label', $this->_('Organization'), 'multiOptions', $empty + $dbLookup->getOrganizations());
        }

        return $model;
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
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Automatic mail jobs');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('automatic mail job', 'automatic mail jobs', $count);
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $lock = $this->util->getCronJobLock();
        if ($lock->isLocked()) {
            $this->addMessage(sprintf($this->_('Automatic mails have been turned off since %s.'), $lock->getLockTime()));

            $request = $this->getRequest();
            if ($menuItem = $this->menu->findController('cron', 'cron-lock')) {
                $menuItem->set('label', $this->_('Turn Automatic Mail Jobs ON'));
            }
        }

        parent::indexAction();

        $this->html->pInfo($this->_('With automatic mail jobs and a cron job on the server, mails can be sent without manual user action.'));
    }

    public function showAction()
    {
        parent::showAction();
        
        $id = $this->getRequest()->getParam('id');
        if (!is_null($id)) {
            $id = (int) $id;
            $job = $this->db->fetchRow("SELECT * FROM gems__comm_jobs WHERE gcj_active = 1 and gcj_id_job = ?", $id);
            if ($job) {
                $model  = $this->loader->getTracker()->getTokenModel();
                $filter = $this->loader->getUtil()->getDbLookup()->getFilterForMailJob($job);
                $params['model'] = $model;
                $params['filter'] = $filter;
                $this->addSnippet('TokenPlanTableSnippet', $params);
             }
        }
    }
}