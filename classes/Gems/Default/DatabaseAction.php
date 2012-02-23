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
    public function createDataTable($tableName, $caption)
    {
        $select = new Zend_Db_Select($this->db);
        $select->from($tableName);

        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($this->_getParam('page'));
        $paginator->setItemCountPerPage(10);

        $table = MUtil_Html_TableElement::createArray($paginator->getCurrentItems(), $caption, true);
        if ($table instanceof MUtil_Html_TableElement) {
            $table->class = 'browser';
            $table->tfrow()->pagePanel($paginator, $this->getRequest(), $this->translate);
        } else {
            $table = MUtil_Html::create()->pInfo(sprintf($this->_('No rows in %s.'), $tableName));
        }

        return $table;
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

        $paths = $this->escort->getDatabasePaths();
        $model = new Gems_Model_DbaModel($this->db, array_values($paths));
        $model->setLocations(array_keys($paths));
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

                if ($count = $this->db->fetchOne($sql)) {

                    $this->addMessage(sprintf($this->_('There are %d rows in the table.'), $count));

                    $this->html->h3(sprintf($this->_('Drop table with %d rows'), $count));
                    $question = $this->_('Are you really sure?');

                    $this->html[] = $this->createDataTable($data['name'], $question);
                    $pInfo = $this->html->pInfo($question, ' ');
                    $pInfo->actionLink(array('confirmed' => 2), $this->_('Yes'));
                    $pInfo->actionLink(array('action' => 'show'), $this->_('No'));
                    $this->html[] = $this->createMenuLinks();

                    return;
                }
            }

            $sql = 'DROP ' . $data['type'] . ' ' . $data['name'];

            try {
                $stmt = $this->db->query($sql);
                $this->addMessage(sprintf($this->_('%1$s %2$s dropped'), $data['name'], $this->_(strtolower($data['type']))));

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
            $table = new Zend_DB_Table($id);

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
        $patcher  = new Gems_Util_DatabasePatcher($this->db, 'patches.sql', $this->escort->getDatabasePaths());
        $changed  = $patcher->uploadPatches($this->loader->getVersions()->getBuild());
        $tableSql = sprintf(
            'SELECT gpa_level AS `%s`, gpa_location AS `%s`, COUNT(*) AS `%s`, COUNT(*) - SUM(gpa_executed) AS `%s`, SUM(gpa_executed) AS `%s`, SUM(gpa_completed) AS `%s` FROM gems__patches GROUP BY gpa_level, gpa_location ORDER BY gpa_level DESC, gpa_location',
            $this->_('Level'),
            $this->_('Subtype'),
            $this->_('Patches'),
            $this->_('To be executed'),
            $this->_('Executed'),
            $this->_('Finished'));

        if ($changed == -1) {
            $this->addMessage($this->_('Create the patch table!'));
        } elseif ($changed) {
            $this->addMessage(sprintf($this->_('%d new or changed patch(es).'), $changed));
        }

        $form = $this->createForm();

        $form->addElement(new MUtil_Form_Element_Exhibitor('app_level', array('label' => $this->_('Gems build'))));
        $form->addElement(new MUtil_Form_Element_Exhibitor('db_level',  array('label' => $this->_('Database build'))));

        $level = new Zend_Form_Element_Text('level', array('label' => $this->_('Execute level')));
        $level->addValidator(new Zend_Validate_Digits());
        $form->addElement($level);

        $form->addElement(new Zend_Form_Element_Checkbox('completed', array('label' => $this->_('Ignore finished'))));
        $form->addElement(new Zend_Form_Element_Checkbox('executed', array('label' => $this->_('Ignore executed'))));
        $form->addElement(new Zend_Form_Element_Submit('show_button',   array('label' => $this->_('Show patches'), 'class' => 'button')));
        $execute = new Zend_Form_Element_Submit('save_button',   array('label' => $this->_('Execute'), 'class' => 'button'));
        $form->addElement($execute);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            if ($form->isValid($data)) {
                if ($execute->isChecked()) {
                    $changed = $patcher->executePatch($data['level'], $data['completed'], $data['executed']);

                    $data['db_level'] = $data['level'];
                    $form->getElement('db_level')->setValue($data['db_level']);

                    $this->addMessage(sprintf($this->_('%d patch(es) executed.'), $changed));

                    //As a lot of cache depends on the database, it is best to clean the cache now
                    //since import tables might have changed
                    $cache = $this->escort->cache;
                    if ($cache instanceof Zend_Cache_Core) {
                        $cache->clean();
                        $this->addMessage($this->_('Cache cleaned'));
                    }
                }

                $tableSql = sprintf(
                    'SELECT gpa_id_patch AS `%s`, gpa_level AS `%s`, gpa_location AS `%s`, gpa_name AS `%s`, gpa_sql AS `%s`, gpa_executed AS `%s`, gpa_completed AS `%s`, gpa_result as `%s` FROM gems__patches WHERE gpa_level = ? ORDER BY gpa_level, gpa_location, gpa_name, gpa_order',
                    $this->_('Patch'),
                    $this->_('Level'),
                    $this->_('Subtype'),
                    $this->_('Name'),
                    $this->_('Query'),
                    $this->_('Executed'),
                    $this->_('Finished'),
                    $this->_('Result'));

                $tableSql = $this->db->quoteInto($tableSql, $form->getValue('level'));
            }

        } else {
            $data['app_level'] = $this->loader->getVersions()->getBuild();
            $data['db_level']  = $this->db->fetchOne('SELECT gpl_level FROM gems__patch_levels ORDER BY gpl_level DESC');
            $data['level']     = min($data['db_level'] + 1, $data['app_level']);
            $data['completed'] = 1;
            $data['executed']  = 0;

            $form->populate($data);
        }

        $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
        $table->setAsFormLayout($form, true, true);
        $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.

        if ($links = $this->createMenuLinks(1)) {
            $table->tf(); // Add empty cell, no label
            $linksCell = $table->tf($links);
        }

        $this->html->h3($this->_('Patch maintenance'));
        $this->html[] = $form;

        if ($data = $this->db->fetchAll($tableSql)) {
            $table = MUtil_Html_TableElement::createArray($data, $this->_('Patch overview'), true);
            $table->class = 'browser';
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
                mkdir($filedir, 0777, true);
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

            $repeater = $model->loadRepeatable($filter);
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
                $results[] = sprintf($this->_('Starting %d object creation scripts.'), $oCount) . '<br/>';
                $i         = 1;
                foreach ($objects as $data) {

                    $result = $model->runScript($data);
                    $results = array_merge($results, $result);
                    $results[] = sprintf($this->_('Finished %s creation script for object %d of %d'), $this->_(strtolower($data['type'])), $i, $oCount) . '<br/>';
                    $i++;
                }
            } else {
                $results[] = $this->_('All objects exist. Nothing was executed.');
            }
            $this->addMessage($results);
            $this->_reroute(array('action' => 'index'), true);
        }

        $this->html->h3($this->_('Create not-existing database objects'));
        if ($objects) {
            $this->html->pInfo(sprintf($this->plural('One database object does not exist.', 'These %d database objects do not exist.', $oCount), $oCount));
            $this->html->h4($this->plural('Are you sure you want to create it?', 'Are you sure you want to create them all?', $oCount));

            $model->set('name', 'itemDisplay', array(__CLASS__, 'createShowLink'), 'tableDisplay', 'em');
            $bridge = new MUtil_Model_TableBridge($model, array('class' => 'browser'));
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

        $element = new Zend_Form_Element_Textarea('script');
        $element->setDescription($this->_('Separate multiple commands with semicolons (;).'));
        $element->setLabel('SQL:');
        $element->setRequired(true);
        $form->addElement($element);

        $element = new Zend_Form_Element_Submit('submit');
        $element->setAttrib('class', 'button');
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

            $this->html->h3(sprintf($this->_('The data in table %s'), $data['name']));
            $this->html[] = $this->createDataTable($data['name'], sprintf($this->_('Contents of %s %s'), $this->_($data['type']), $data['name']));
            $this->html[] = $this->createMenuLinks();
        }
    }
}
