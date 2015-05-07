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
 * Controller for Source maintenance
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_SourceAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'extraSort'   => array('gso_source_name' => SORT_ASC),
        );

    /**
     * Array of the actions that use a summarized version of the model.
     *
     * This determines the value of $detailed in createAction(). As it is usually
     * less of a problem to use a $detailed model with an action that should use
     * a summarized model and I guess there will usually be more detailed actions
     * than summarized ones it seems less work to specify these.
     *
     * @var array $summarizedActions Array of the actions that use a
     * summarized version of the model.
     */
    public $summarizedActions = array('index', 'autofilter', 'check-all', 'attributes-all', 'synchronize-all');

    /**
     * Displays textual information what checking tokens does
     *
     * @param \MUtil_Html_Sequence $html
     * @param \Zend_Translate $translate
     * @param string $itemDescription Describe which tokens will be checked
     */
    public static function addCheckInformation(\MUtil_Html_Sequence $html, \Zend_Translate $translate, $itemDescription)
    {
        $html->pInfo($translate->_('Check tokens for being answered or not, reruns survey and round event code on completed tokens and recalculates the start and end times of all tokens in tracks that have completed tokens.'));
        $html->pInfo($translate->_('Run this code when survey result fields, survey or round events or the event code has changed or after bulk changes in a survey source.'));
        $html->pInfo($itemDescription);
    }


    /**
     * Displays a textual explanation what synchronization does on the page.
     */
    protected function addSynchronizationInformation()
    {
        $this->html->pInfo($this->_('Check source for new surveys, changes in survey status and survey deletion. Can also perform maintenance on some sources, e.g. by changing the number of attributes.'));
        $this->html->pInfo($this->_('Run this code when the status of a survey in a source has changed or when the code has changed and the source must be adapted.'));
    }

    /**
     * Check token attributes for a single source
     */
    public function attributesAction()
    {
        $sourceId = $this->getSourceId();
        $where    = $this->db->quoteInto('gsu_id_source = ?', $sourceId);

        $batch = $this->loader->getTracker()->refreshTokenAttributes('attributeCheck' . $sourceId, $where);

        $title = sprintf($this->_('Refreshing token attributes for %s source.'),
                    $this->db->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", $sourceId));

        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        $this->html->pInfo($this->_('Refreshes the attributes for a token as stored in the source.'));
        $this->html->pInfo($this->_('Run this code when the number of attributes has changed or when you suspect the attributes have been corrupted somehow.'));
    }

    /**
     * Check all token attributes for all sources
     */
    public function attributesAllAction()
    {
        $batch = $this->loader->getTracker()->refreshTokenAttributes('attributeCheckAll');

        $title = $this->_('Refreshing token attributes for all sources.');

        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        $this->html->pInfo($this->_('Refreshes the attributes for a token as stored in on of the sources.'));
        $this->html->pInfo($this->_('Run this code when the number of attributes has changed or when you suspect the attributes have been corrupted somehow.'));
    }

    /**
     * Check all the tokens for a single source
     */
    public function checkAction()
    {
        $sourceId = $this->getSourceId();
        $where    = $this->db->quoteInto('gto_id_survey IN (SELECT gsu_id_survey FROM gems__surveys WHERE gsu_id_source = ?)', $sourceId);

        $batch = $this->loader->getTracker()->recalculateTokens('sourceCheck' . $sourceId, $this->loader->getCurrentUser()->getUserId(), $where);

        $title = sprintf($this->_('Checking survey results for %s source.'),
                    $this->db->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", $sourceId));
        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        self::addCheckInformation($this->html, $this->translate, $this->_('This task checks all tokens in this source.'));
    }

    /**
     * Check all the tokens for all sources
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheckAll', $this->loader->getCurrentUser()->getUserId());

        $title = $this->_('Checking survey results for all sources.');
        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        self::addCheckInformation($this->html, $this->translate, $this->_('This task checks all tokens in all sources.'));
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
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $tracker = $this->loader->getTracker();
        $model   = new \MUtil_Model_TableModel('gems__sources');

        $model->set('gso_source_name', 'label', $this->_('Name'),
                'description', $this->_('E.g. the name of the project - for single source projects.'),
                'size', 15,
                'minlength', 4,
                'validator', $model->createUniqueValidator('gso_source_name')
                );
        $model->set('gso_ls_url',      'label', $this->_('Source Url'),
                'default', 'http://',
                'description', $this->_('For creating token-survey url.'),
                'size', 50,
                'validators[unique]', $model->createUniqueValidator('gso_ls_url'),
                'validators[url]', new \MUtil_Validate_Url()
                );

        $sourceClasses = $tracker->getSourceClasses();
        end($sourceClasses);
        $model->set('gso_ls_class',    'label', $this->_('Adaptor class'),
                'default', key($sourceClasses),
                'multiOptions', $sourceClasses
                );
        $model->set('gso_ls_adapter',  'label', $this->_('Database Server'),
                'default', substr(get_class($this->db), strlen('Zend_Db_Adapter_')),
                'description', $this->_('The database server used by the source.'),
                'multiOptions', $tracker->getSourceDatabaseClasses()
                );
        $model->set('gso_ls_table_prefix', 'label', $this->_('Table prefix'),
                'default', 'ls__',
                'description', $this->_('Do not forget the underscores.'),
                'size', 15
                );


        if ($detailed) {
            $in_gems = $this->_('Leave empty for the Gems database.');

            $model->set('gso_ls_dbhost',       'label', $this->_('Database host'),
                    'description', $in_gems,
                    'size', 15
                    );
            $model->set('gso_ls_database',     'label', $this->_('Database'),
                    'description', $in_gems,
                    'size', 15
                    );
            $model->set('gso_ls_username',     'label', $this->_('Database Username'),
                    'description', $in_gems,
                    'size', 15
                    );

            $model->set('gso_ls_password',     'label', $this->_('Database Password'),
                    'elementClass', 'Password',
                    'repeatLabel', $this->_('Repeat password'),
                    'required', false,
                    'size', 15
                    );
            if ('create' == $action) {
                $model->set('gso_ls_password', 'description', $in_gems, 'renderPassword', true);
            } else {
                $model->set('gso_ls_password', 'description', $this->_('Enter only when changing'),
                        'renderPassword', false);
            }
            $type = new \Gems_Model_Type_EncryptedField($this->project, true);
            $type->apply($model, 'gso_ls_password', 'gso_encryption');

            $model->set('gso_ls_charset',     'label', $this->_('Charset'),
                    'description', $in_gems,
                    'size', 15
                    );
            $model->set('gso_active',         'label', $this->_('Active'),
                    'default', 0,
                    'multiOptions', $this->util->getTranslated()->getYesNo()
                    );
        }

        $model->set('gso_status',             'label', $this->_('Status'),
                'default', 'Not checked',
                'elementClass', 'Exhibitor'
                );
        $model->set('gso_last_synch',         'label', $this->_('Last synch'),
                'elementClass', 'Exhibitor'
                );

        \Gems_Model::setChangeFieldsByPrefix($model, 'gso');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Survey Sources');
    }

    /**
     * Load a source object
     *
     * @param int $sourceId
     * @return \Gems_Tracker_Source_SourceInterface
     */
    private function getSourceById($sourceId = null)
    {
        if (null === $sourceId) {
            $sourceId = $this->getSourceId();
        }
        return $this->loader->getTracker()->getSource($sourceId);
    }

    /**
     * The id of the current source
     *
     * @return int
     */
    private function getSourceId()
    {
        $sourceId = $this->_getParam('gso_id_source');
        if (! $sourceId) {
            $sourceId = $this->_getIdParam();
        }

        return $sourceId;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('source', 'sources', $count);
    }

    /**
     * Action to check whether the source is active
     */
    public function pingAction()
    {
        $source = $this->getSourceById();

        if ($source->checkSourceActive($this->loader->getCurrentUser()->getUserId())) {
            $this->addMessage($this->_('This installation is active.'));
        } else {
            $this->addMessage($this->_('Inactive installation.'));
        }

        $this->_reroute(array($this->getRequest()->getActionKey() => 'show'));
    }

    /**
     * Synchronize survey status for the surveys in a source
     */
    public function synchronizeAction()
    {
        $sourceId = $this->getSourceId();

        $batch = $this->loader->getTracker()->synchronizeSources($sourceId, $this->loader->getCurrentUser()->getUserId());

        $title = sprintf($this->_('Synchronize the %s source.'),
                    $this->db->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", $sourceId));
        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        $this->addSynchronizationInformation();
    }

    /**
     * Synchronize survey status for the surveys in all sources
     */
    public function synchronizeAllAction()
    {
        //*
        $batch = $this->loader->getTracker()->synchronizeSources(null, $this->loader->getCurrentUser()->getUserId());
        $batch->minimalStepDurationMs = 3000;

        $title = $this->_('Synchronize all sources.');
        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        $this->html->actionLink(array('action' => 'index'), $this->_('Cancel'), array('class' => 'btn-danger'));

        $this->addSynchronizationInformation();
    }
}
