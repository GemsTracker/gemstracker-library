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
 * Standard controller for database creation and maintenance.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_DatabaseAction  extends Gems_Controller_BrowseEditAction
{
    public $sortKey = array('group' => SORT_ASC, 'type' => SORT_ASC, 'name' => SORT_ASC);

    /**
     * Set the parameters needed by the menu.
     *
     * @param array $data The current model item
     */
    private function _setMenuParameters(array $data)
    {
        $source = $this->menu->getParameterSource();
        $source['script'] = $data['script'] ? true : false;
        $source['exists'] = $data['exists'] ? true : false;
    }

    /**
     * Make sure the cache is cleaned.
     *
     * As a lot of cache depends on the database, it is best to clean the cache
     * now since import tables might have changed.
     *
     * @return void
     */
    private function _cleanCache()
    {
        if ($this->cache instanceof Zend_Cache_Core) {
            $this->cache->clean();
            $this->addMessage($this->_('Cache cleaned'));
        }
    }

    public function createDataTable($tableName, $caption, Zend_Db_Adapter_Abstract $db)
    {
        $select = $db->select();
        $select->from($tableName);

        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($this->_getParam('page'));
        $paginator->setItemCountPerPage(10);

        $table = MUtil_Html_TableElement::createArray($paginator->getCurrentItems(), $caption, true);
        if ($table instanceof MUtil_Html_TableElement) {
            $table->class = 'browser table table-striped table-bordered table-hover';
            $table->tfrow()->pagePanel($paginator, $this->getRequest(), $this->translate);
        } else {
            $table = MUtil_Html::create()->pInfo(sprintf($this->_('No rows in %s.'), $tableName));
        }

        $container = MUtil_Html::create()->div(array('class' => 'table-responsive'));
        $container[] = $table;
        return $container;
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
        $moreDetails = ! in_array($action, array('run', 'deleted'));

        $model = new Gems_Model_DbaModel($this->db, $this->escort->getDatabasePaths());
        if ($this->project->databaseFileEncoding) {
            $model->setFileEncoding($this->project->databaseFileEncoding);
        }


        $model->set('type',         'label', $this->_('Type'));
        $model->set('name',         'label', $this->_('Name'));

        if ($moreDetails) {
            $model->set('group',    'label', $this->_('Group'));
            $model->set('order',    'label', $this->_('Order'));
            $model->set('location', 'label', $this->_('Location'));
        }
        // $model->set('path',      'label', $this->_('Path'));
        $model->set('state',        'label', $this->_('Status'));

        if ($detailed) {
            $model->set('script',   'label', $this->_('Script'), 'itemDisplay', 'pre');
        } else {
            $model->set('lastChanged', 'label', $this->_('Changed on'), 'dateFormat', 'yyyy-MM-dd HH:mm:ss');
        }

        return $model;
    }

    public static function createShowLink($for)
    {
        return MUtil_Html::create()->a(array('action' => 'show', MUtil_Model::REQUEST_ID => $for), $for);
    }

    public function deleteAction()
    {
        $model = $this->getModel();
        $data  = $model->applyRequest($this->getRequest())->loadFirst();

        $this->_setMenuParameters($data);

        if (! ($data && isset($data['exists']) && $data['exists'])) {
            $this->addMessage($this->_('This database object does not exist. You cannot delete it.'));
            $this->html->buttonDiv($this->createMenuLinks(1));
            return;
        }

        if ($this->isConfirmedItem($this->_('Drop %s'))) {

            if (($data['type'] == 'table') && ($this->_getParam('confirmed') == 1)) {

                $sql = 'SELECT COUNT(*) FROM ' . $data['name'];

                if ($count = $data['db']->fetchOne($sql)) {

                    $this->addMessage(sprintf($this->_('There are %d rows in the table.'), $count));

                    $this->html->h3(sprintf($this->_('Drop table with %d rows'), $count));
                    $question = $this->_('Are you really sure?');

                    $this->html[] = $this->createDataTable($data['name'], $question, $data['db']);
                    $pInfo = $this->html->pInfo($question, ' ');
                    $pInfo->actionLink(array('confirmed' => 2), $this->_('Yes'));
                    $pInfo->actionLink(array('action' => 'show'), $this->_('No'));
                    $this->html[] = $this->createMenuLinks();

                    return;
                }
            }

            $sql = 'DROP ' . $data['type'] . ' ' . $data['name'];

            try {
                $stmt = $data['db']->query($sql);
                $this->addMessage(sprintf($this->_('%1$s %2$s dropped'), $data['name'], $this->_(strtolower($data['type']))));
                $this->_cleanCache();

                $model->save(array('exists' => false), $model->getFilter());

            } catch (Zend_Db_Statement_Exception $e) {
                $this->addMessage($e->getMessage() . $this->_(' during statement ') . '<pre>' . $sql . '</pre>');
            }
            return $this->_reroute(array('action' => 'show'));
        }
    }

    public function getFieldTable($id)
    {
        $tData = $this->getModel()->loadTable($id);

        if (! $tData) {
            return sprintf($this->_('%s no longer exists in the database.'), $id);
        }
        if (Gems_Model_DbaModel::STATE_DEFINED == $tData['state']) {
            return sprintf($this->_('%s does not yet exist in the database.'), ucfirst($tData['type']));
        }
        if ('table' !== $tData['type']) {
            return sprintf($this->_('%s object does exist.'), ucfirst($tData['type']));
        }

        try {
            $table = new Zend_DB_Table(array(
                Zend_Db_Table_Abstract::NAME => $id,
                Zend_Db_Table_Abstract::ADAPTER => $tData['db'],
                ));

            $data = MUtil_Lazy::repeat($table->info('metadata'));

            $html = new MUtil_Html_TableElement($data);
            $html->addColumn($data->COLUMN_NAME, 'Column');
            $html->addColumn($data->DATA_TYPE,   'Type');
            $html->addColumn($data->LENGTH, 'Length');
            $html->addColumn($data->SCALE, 'Precision');
            $html->addColumn($data->UNSIGNED, 'Unsigned');
            $html->addColumn($data->NULLABLE, 'Nullable');
            $html->addColumn($data->DEFAULT, 'Default');

        } catch (Zend_Db_Table_Exception $zdte) {
            $html = $this->_('Object is not a table.');
        }

        return $html;
    }

    /**
     * Creates from the model a MUtil_Html_TableElement for display of a single item.
     *
     * It can and will display multiple items, but that is not what this function is for.
     *
     * @param integer $columns The number of columns to use for presentation
     * @param mixed $filter A valid filter for MUtil_Model_ModelAbstract->load()
     * @param mixed $sort A valid sort for MUtil_Model_ModelAbstract->load()
     * @return MUtil_Html_TableElement
     */
    public function getShowTable($columns = 1, $filter = null, $sort = null)
    {
        $table = parent::getShowTable($columns, $filter, $sort);

        $model = $this->getModel();

        if ($model->isMeta('action', 'show')) {
            $table->tr();
            $table->tdh($this->_('Structure'));
            $table->td($this->getFieldTable($this->_getParam(MUtil_Model::REQUEST_ID)));
        }

        return $table;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('database object', 'database objects', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Database object overview');
    }

    public function patchAction()
    {
        $this->html->h3($this->_('Patch maintenance'));

        $patcher  = new Gems_Util_DatabasePatcher($this->db, 'patches.sql', $this->escort->getDatabasePaths());
        $tableSql = sprintf(
            'SELECT gpa_level AS `%s`, gpa_location AS `%s`, COUNT(*) AS `%s`, COUNT(*) - SUM(gpa_executed) AS `%s`, SUM(gpa_executed) AS `%s`, SUM(gpa_completed) AS `%s`, MAX(gpa_changed) AS `%s` FROM gems__patches GROUP BY gpa_level, gpa_location ORDER BY gpa_level DESC, gpa_location',
            $this->_('Level'),
            $this->_('Subtype'),
            $this->_('Patches'),
            $this->_('To be executed'),
            $this->_('Executed'),
            $this->_('Finished'),
            $this->_('Changed on'));

        $form = $this->createForm();
        $form->setName('database_patcher');

        $form->addElement($form->createElement('exhibitor', 'app_level', array('label' => $this->_('Gems build'))));
        $form->addElement($form->createElement('exhibitor', 'db_level', array('label' => $this->_('Database build'))));

        $level = $form->createElement('text', 'level', array('label' => $this->_('Execute level')));
        $level->addValidator(new Zend_Validate_Digits());
        $form->addElement($level);

        $form->addElement($form->createElement('checkbox', 'completed', array('label' => $this->_('Ignore finished'))));
        $form->addElement($form->createElement('checkbox', 'executed', array('label' => $this->_('Ignore executed'))));
        $form->addElement($form->createElement('submit', 'show_button',   array('label' => $this->_('Show patches'), 'class' => 'button')));
        // $execute = new Zend_Form_Element_Submit('save_button',   array('label' => $this->_('Execute'), 'class' => 'button'));
        // $form->addElement($execute);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            if ($form->isValid($data)) {
                $batch = $this->loader->getTaskRunnerBatch(__CLASS__ . $data['level']);
                $batch->setFormId($form->getId());
                if (! $batch->isLoaded()) {
                    $patcher->loadPatchBatch($data['level'], $data['completed'], $data['executed'], $batch);
                }

                $this->_helper->batchRunner($batch, sprintf($this->_('Executing patch level %d'), $data['level']));

                $data['db_level'] = $data['level'];
                $form->getElement('db_level')->setValue($data['db_level']);

                $tableSql = sprintf(
                    'SELECT gpa_id_patch AS `%s`, gpa_level AS `%s`, gpa_location AS `%s`, gpa_name AS `%s`, gpa_sql AS `%s`, gpa_executed AS `%s`, gpa_completed AS `%s`, gpa_result AS `%s`, gpa_changed AS `%s` FROM gems__patches WHERE gpa_level = ? ORDER BY gpa_level, gpa_changed DESC, gpa_location, gpa_name, gpa_order',
                    $this->_('Patch'),
                    $this->_('Level'),
                    $this->_('Subtype'),
                    $this->_('Name'),
                    $this->_('Query'),
                    $this->_('Executed'),
                    $this->_('Finished'),
                    $this->_('Result'),
                    $this->_('Changed on'));

                $tableSql = $this->db->quoteInto($tableSql, $data['level']);

                // Hide the form: it is needed for the batch post, but we do not want it visible
                $form->setAttrib('style', 'display: none;');
            }

        } else {
            $changed = $patcher->uploadPatches($this->loader->getVersions()->getBuild());
            if ($changed == -1) {
                $this->addMessage($this->_('Create the patch table!'));
            } elseif ($changed) {
                $this->addMessage(sprintf($this->_('%d new or changed patch(es).'), $changed));
                $this->cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('sess_' . session_id()));
            }

            $data['app_level'] = $this->loader->getVersions()->getBuild();
            $data['db_level']  = $this->db->fetchOne('SELECT gpl_level FROM gems__patch_levels ORDER BY gpl_level DESC');
            $data['level']     = min($data['db_level'] + 1, $data['app_level']);
            $data['completed'] = 1;
            $data['executed']  = 0;

            $form->populate($data);
        }

        //$table = new MUtil_Html_TableElement(array('class' => 'formTable'));
        //$table->setAsFormLayout($form, true, true);
        //$table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.

        if ($links = $this->createMenuLinks(1)) {
            //$table->tf(); // Add empty cell, no label
            //$linksCell = $table->tf($links);
        }

        $this->html[] = $form;

        if ($data = $this->db->fetchAll($tableSql)) {
            $table = MUtil_Html_TableElement::createArray($data, $this->_('Patch overview'), true);
            $table->class = 'browser table table-striped table-bordered table-hover';
            $this->html[] = $table;
        }
    }

    public function refreshTranslationsAction()
    {
        $translations = array();

        foreach ($this->getModel()->load() as $item) {
            if ($field = $item['type']) {
                $translations[$field] = $field;
            }
        }

        if (isset($this->project->databaseTranslations)) {
            foreach ($this->project->databaseTranslations as $table => $fields) {

                $sql = 'SELECT ' . $fields . ' FROM ' . $table;

                $rows = $this->db->fetchAll($sql, array(), Zend_Db::FETCH_NUM);

                foreach ($rows as $row) {
                    foreach ($row as $field) {
                        if ($field) {
                            $translations[$field] = $field;
                        }
                    }
                }
            }
        }

        if ($translations) {
            $filedir  = APPLICATION_PATH . '/languages';
            if (! file_exists($filedir)) {
                @mkdir($filedir, 0777, true);
            }

            $filename = $filedir . '/' . GEMS_PROJECT_NAME . 'DatabaseTranslations.php';

            $content  = "<?php\n\n/**\n *This file contains fake translation calls\n * for values in database fields \n */\n\n_('";
            $content .= implode("');\n_('", $translations);
            $content .= "');\n\n// End of translations.\n\n";

            file_put_contents($filename, $content);

            $this->addMessage(sprintf('%d translatable fields stored in %s.', count($translations), $filename));
        } else {
            $this->addMessage('No translatable fields found.');
        }
        $this->_reroute(array('action' => 'index'), true);
    }

    public function runAction()
    {
        $model = $this->getModel();
        $data  = $model->loadFirst();

        $this->_setMenuParameters($data);

        if (! ($data && isset($data['exists'], $data['script']) && ($data['exists'] || $data['script']))) {
            $this->addMessage($this->_('This database object does not exist. You cannot create it.'));
            $this->html->buttonDiv($this->createMenuLinks(1));
            return;
        }

        if (! $data['script']) {
            $this->addMessage($this->_('This database object has no script. You cannot execute it.'));

            $repeater = $model->loadRepeatable();
            $table    = $this->getShowTable();
            $table->setRepeater($repeater);

            $this->html[] = $table;
            $this->html->buttonDiv($this->createMenuLinks());
            return;
        }

        if ($this->isConfirmedItem($this->_('Run %s'))) {
            $model  = $this->getModel();
            $data   = $model->loadFirst();

            $results = $model->runScript($data);

            $this->addMessage($results);
            $this->_cleanCache();
            $this->_reroute(array('action' => 'show'));
        }
    }

    public function runAllAction()
    {
        $model   = $this->getModel();
        $objects = $model->load(array('exists' => false), array('order' => SORT_ASC) + $this->sortKey);
        $oCount  = count($objects);

        if ($this->_getParam('confirmed')) {
            if ($objects) {
                $results = array();
                $results[] = sprintf($this->_('Starting %d object creation scripts.'), $oCount);
                $i         = 1;
                foreach ($objects as $data) {

                    $result = $model->runScript($data);
                    $results = array_merge($results, $result);
                    $results[] = sprintf($this->_('Finished %s creation script for object %d of %d'), $this->_(strtolower($data['type'])), $i, $oCount);
                    $i++;
                }

            } else {
                $results[] = $this->_('All objects exist. Nothing was executed.');
            }
            $this->addMessage($results);
            $this->_cleanCache();
            $this->_reroute(array('action' => 'index'), true);
        }

        $this->html->h3($this->_('Create not-existing database objects'));
        if ($objects) {
            $this->html->pInfo(sprintf($this->plural('One database object does not exist.', 'These %d database objects do not exist.', $oCount), $oCount));
            $this->html->h4($this->plural('Are you sure you want to create it?', 'Are you sure you want to create them all?', $oCount));

            $model->set('name', 'itemDisplay', array(__CLASS__, 'createShowLink'), 'tableDisplay', 'em');
            $bridge = $model->getBridgeFor('table', array('class' => 'browser table table-striped table-bordered table-hover'));
            $bridge->setRepeater($objects);
            foreach (array('order', 'group', 'type', 'name', 'location') as $key) {
                $bridge->add($key);
            }
            $this->html->append($bridge->getTable());

            $this->html->actionLink(array('confirmed' => 1), $this->_('Yes'));
            $this->html->actionLink(array('action' => 'index'), $this->_('No'));
        } else {
            $this->html->pInfo($this->_('All database objects exist. There is nothing to create.'));
            $this->html->actionLink(array('action' => 'index'), $this->_('Cancel'));
        }
    }

    public function runSqlAction()
    {
        /*************
         * Make form *
         *************/
        $form = $this->createForm();

        $element = $form->createElement('textarea', 'script');
        $element->setDescription($this->_('Separate multiple commands with semicolons (;).'));
        $element->setLabel('SQL:');
        $element->setRequired(true);
        $form->addElement($element);

        //$element = new Zend_Form_Element_Submit('submit');
        $element = $form->createElement('submit', 'submit');
        $element->setLabel($this->_('Run'));
        $form->addElement($element);

        /****************
         * Process form *
         ****************/
        if ($this->_request->isPost() && $form->isValid($_POST)) {
            $data = $_POST;
            $data['name'] = '';
            $data['type'] = $this->_('raw');

            $model = $this->getModel();
            $results   = $model->runScript($data, true);
            $resultSet = 1;
            $echos     = MUtil_Html::create()->array();
            foreach ($results as $result) {
                if (is_string($result)) {
                    $this->addMessage($result);
                } else {
                    $echo = $echos->echo($result, sprintf($this->_('Result set %s.'), $resultSet++));
                    $echo->class = 'browser';
                }
            }

        } else {
            $form->populate($_POST);
            $resultSet = 0;
        }

        /****************
         * Display form *
         ****************/
        $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
        $table->setAsFormLayout($form, true, true);
        $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
        $table['tbody'][0][0]->style = 'vertical-align: top;'; // Only single cell, this always looks better here.

        if ($links = $this->createMenuLinks()) {
            $table->tf(); // Add empty cell, no label
            $linksCell = $table->tf($links);
        }

        $this->html->h3($this->_('Execute raw SQL'));
        $this->html[] = $form;
        if ($resultSet > 1) {
            $this->html->h3($this->_('Result sets'));
            $this->html[] = $echos;
        }
    }

    public function showAction()
    {
        $model  = $this->getModel();
        $data   = $model->loadFirst();

        if ($data) {
            $this->_setMenuParameters($data);
        }

        parent::showAction();
    }

    public function viewAction()
    {
        $model  = $this->getModel();
        $data   = $model->loadFirst();

        $this->_setMenuParameters($data);

        if (! ($data && isset($data['exists']) && $data['exists'])) {
            $this->addMessage($this->_('This database object does not exist. You cannot view it.'));
            $this->html->buttonDiv($this->createMenuLinks(1));

        } else {
            if (isset($data['db'])) {
                $db = $data['db'];
            } else {
                $db = $this->db;
            }
            $this->html->h3(sprintf($this->_('The data in table %s'), $data['name']));
            $this->html[] = $this->createDataTable(
                    $data['name'],
                    sprintf($this->_('Contents of %s %s'), $this->_($data['type']), $data['name']),
                    $db);
            $this->html[] = $this->createMenuLinks();
        }
    }
}
