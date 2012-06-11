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
class Gems_Default_SourceAction  extends Gems_Controller_BrowseEditAction
{
    public $autoFilter = false;

    public $summarizedActions = array('index', 'autofilter', 'synchronize-all');

    public $sortKey = array('gso_source_name' => SORT_ASC);

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        $in_gems = $this->_('Leave empty for the Gems database.');

        if ($new) {
            $model->set('gso_ls_password', 'description', $in_gems);
        } else {
            $model->set('gso_ls_password', 'description', $this->_('Enter only when changing'));
            $model->setSaveWhenNotNull('gso_ls_password');
        }

        $bridge->addHidden('gso_id_source');
        $bridge->addText('gso_source_name', 'description', $this->_('E.g. the name of the project - for single source projects.'), 'size', 15, 'minlength', 4);
        $bridge->addValidator('gso_source_name', $model->createUniqueValidator('gso_source_name'));
        $bridge->addText('gso_ls_url', 'description', $this->_('For creating token-survey url.'), 'size', 50);
        $bridge->addValidator('gso_ls_url', $model->createUniqueValidator('gso_ls_url'));
        $bridge->addValidator('gso_ls_url', new MUtil_Validate_Url());

        $bridge->addSelect('gso_ls_class');
        $bridge->addSelect('gso_ls_adapter', 'description', $this->_('The database server used by the source.'));

        $bridge->addText('gso_ls_table_prefix', array('size' => 15, 'description' => $this->_('Do not forget the underscores.')));
        $bridge->addText('gso_ls_dbhost', array('size' => 15, 'description' => $in_gems));
        $bridge->addText('gso_ls_database', array('size' => 15, 'description' => $in_gems));
        $bridge->addText('gso_ls_username', array('label' => $this->_('Database Username'), 'size' => 15, 'description' => $in_gems));
        $bridge->addPassword('gso_ls_password',
            'label', $this->_('Database Password'),
            'renderPassword', $new,
            'repeatLabel', $this->_('Repeat password'),
            'required', false,
            'size', 15
        );
        $bridge->addText('gso_ls_charset', array('size' => 15, 'description' => $in_gems));

        $bridge->addExhibitor('gso_active', array('label' => $this->_('Active'), 'multiOptions' => $this->util->getTranslated()->getYesNo()));
        $bridge->addExhibitor('gso_status');
        $bridge->addExhibitor('gso_last_synch');
    }

    /**
     * Check all token attributes for a single source
     */
    public function attributesAction()
    {
        $sourceId = $this->getSourceId();
        $where    = $this->db->quoteInto('gsu_id_source = ?', $sourceId);

        $batch = $this->loader->getTracker()->refreshTokenAttributes('sourceCheck' . $sourceId, $where);

        $title = sprintf($this->_('Refreshing token attributes for %s source.'),
                    $this->db->fetchOne("SELECT gso_source_name FROM gems__sources WHERE gso_id_source = ?", $sourceId));

        $this->_helper->BatchRunner($batch, $title);
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
        $this->_helper->BatchRunner($batch, $title);
    }

    /**
     * Check all the tokens for all sources
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheckAll', $this->loader->getCurrentUser()->getUserId());

        $title = $this->_('Checking survey results for all sources.');
        $this->_helper->BatchRunner($batch, $title);
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
    public function createModel($detailed, $action)
    {
        $tracker = $this->loader->getTracker();
        $model   = new MUtil_Model_TableModel('gems__sources');

        $model->set('gso_source_name', 'label', $this->_('Name'));
        $model->set('gso_ls_url',      'label', $this->_('Source Url'), 'default', 'http://');

        $model->set('gso_ls_class',    'label', $this->_('Adaptor class'), 'multiOptions', $tracker->getSourceClasses());
        if ($detailed) {
            $model->set('gso_ls_adapter',  'label', $this->_('Database Server'), 'multiOptions', $tracker->getSourceDatabaseClasses(), 'default', substr(get_class($this->db), strlen('Zend_Db_Adapter_')));

            $model->set('gso_ls_dbhost',       'label', $this->_('Database host'));
            $model->set('gso_ls_database',     'label', $this->_('Database'));
            $model->set('gso_ls_charset',     'label', $this->_('Charset'));
        }

        $model->set('gso_ls_table_prefix', 'label', $this->_('Table prefix'), 'default', 'ls__');

        $model->set('gso_active', 'default', 0);
        $model->set('gso_status', 'label', $this->_('Status'), 'default', 'Not checked');
        $model->set('gso_last_synch', 'label', $this->_('Last check'));

        Gems_Model::setChangeFieldsByPrefix($model, 'gso');

        return $model;
    }

    /**
     * Load a source object
     *
     * @param int $sourceId
     * @return Gems_Tracker_Source_SourceInterface
     */
    private function getSourceById($sourceId = null)
    {
        if (null === $sourceId) {
            $sourceId = $this->getSourceId();
        }
        return $this->loader->getTracker()->getSource($sourceId);
    }

    private function getSourceId()
    {
        $sourceId = $this->_getParam('gso_id_source');
        if (! $sourceId) {
            $sourceId = $this->_getIdParam();
        }

        return $sourceId;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('source', 'sources', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Survey Sources');
    }

    public function pingAction()
    {
        $source = $this->getSourceById();

        if ($source->checkSourceActive($this->loader->getCurrentUser()->getUserId())) {
            $this->addMessage($this->_('This installation is active.'));
        } else {
            $this->addMessage($this->_('Inactive installation.'));
        }

        $this->afterSaveRoute($this->getRequest());
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
        $this->_helper->BatchRunner($batch, $title);
    }

    /**
     * Synchronize survey status for the surveys in all sources
     */
    public function synchronizeAllAction()
    {
        //*
        $batch = $this->loader->getTracker()->synchronizeSources(null, $this->loader->getCurrentUser()->getUserId());

        $title = $this->_('Synchronize all sources.');
        $this->_helper->BatchRunner($batch, $title);

        $this->html->actionLink(array('action' => 'index'), $this->_('Cancel'));
    }
}
