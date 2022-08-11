<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 * Standard controller for database creation and maintenance.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class DatabaseAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    public $accesslog;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'extraSort' => array('group' => SORT_ASC, 'type' => SORT_ASC, 'name' => SORT_ASC),
        );

    /**
     * @var \Gems\Cache\HelperAdapter
     */
    public $cache;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     * Tradition way of setting default sort (still in use)
     *
     * @var array
     */
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
        if ($this->cache instanceof \Gems\Cache\HelperAdapter) {
            $this->cache->clear();
            $this->addMessage($this->_('Cache cleaned'));
        }
    }

    public function createDataTable($tableData, $caption)
    {
        $db        = isset($tableData['db']) ? $tableData['db'] : $this->db;
        $tableName = $tableData['name'];
        $type      = $tableData['type'];

        // We can only use a different db when supplying a \Zend_Db_Table_Abstract
        if ($type == 'view') {
            $table = new \Gems\Db\TableView(['name' => $tableName, 'db'=> $db]);
        } else {
            $table = new \Zend_Db_Table(['name' => $tableName, 'db'=> $db]);
        }
        $model = new \MUtil\Model\TableModel($table);

        // Add labels so they show in the table
        foreach ($model->getItemNames() as $item) {
            $model->set($item, 'label', $item);
        }
        // We don't want the ID applied to the primary key, so we set a fake key
        $model->setKeys(['fakeKey'=>'fakeKey']);

        $params = [
            'browse'   => true,
            'caption'  => $caption,
            'model'    => $model,
            'onEmpty'  => sprintf($this->_('No rows in %s.'), $tableName),
            'showMenu' => false,
        ];

        $this->addSnippet('ModelTableSnippetGeneric', $params);
    }

    /**
     * Retrieve a form object and add extra decorators
     *
     * @param array $options
     * @return \Gems\Form
     */
    public function createForm($options = null)
    {
        $options['class'] = 'form-horizontal';
        $options['role'] = 'form';
        return new \Gems\Form($options);
    }

    /**
     *
     * @param int $includeLevel
     * @param string $parentLabel
     * @return array
     */
    protected function createMenuLinks($includeLevel = 2, $parentLabel = true)
    {
        if ($currentItem  = $this->menu->getCurrent()) {
            $links        = array();
            $childItems   = $currentItem->getChildren();
            $parameters   = $currentItem->getParameters();
            $request      = $this->getRequest();
            $showDisabled = $includeLevel > 99;
            $menuSource   = $this->menu->getParameterSource();

            if ($parentItem = $currentItem->getParent()) {
                // Add only if not toplevel.
                if (($parentItem instanceof \Gems\Menu\SubMenuItem) && $parentItem->has('controller')) {
                    $key = $parentItem->get('controller') . '.' . $parentItem->get('action');
                    if ($parentLabel) {
                        if (true === $parentLabel) {
                            $parentLabel = $this->_('Cancel');
                        }
                        $links[$key] = $parentItem->toActionLink($request, $this, $menuSource, $parentLabel);
                    } else {
                        $links[$key] = $parentItem->toActionLink($request, $this, $menuSource);
                    }
                    if ($includeLevel > 1) {
                        $childItems = array_merge($parentItem->getChildren(), $childItems);
                    }
                }
            }

            if ($includeLevel < 1) {
                return $links;
            }

            // The reset parameter blocks the display of buttons, so we unset it
            unset($parameters['reset']);
            if ($childItems) {
                foreach ($childItems as $menuItem) {
                    if ($menuItem !== $currentItem) {
                        // Select only children with the same parameters
                        if ($menuItem->getParameters() == $parameters) {
                            // And buttons only if include level higher than 2.
                            if (($includeLevel > 2) || (! $menuItem->get('button_only'))) {
                                if ($link = $menuItem->toActionLink($request, $this, $menuSource, $showDisabled)) {
                                    $key = $menuItem->get('controller') . '.' . $menuItem->get('action');
                                    $links[$key] = $link;
                                }
                            }
                        }
                    }
                }
            }

            return $links;
        }
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
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $moreDetails = ! in_array($action, array('run', 'deleted'));

        $model = new \Gems\Model\DbaModel($this->db, $this->escort->getDatabasePaths());
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
            $model->set('lastChanged', 'label', $this->_('Changed on'));
        }

        return $model;
    }

    public static function createShowLink($for)
    {
        return \MUtil\Html::create()->a(array('action' => 'show', \MUtil\Model::REQUEST_ID => $for), $for);
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

                    $this->createDataTable($data, $question);
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

            } catch (\Zend_Db_Statement_Exception $e) {
                $this->addMessage($e->getMessage() . $this->_(' during statement ') . '<pre>' . $sql . '</pre>');
            }
            return $this->_reroute(array('action' => 'show'));
        }
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Database object overview');
    }

    /**
     * Creates from the model a \MUtil\Html\TableElement for display of a single item.
     *
     * It can and will display multiple items, but that is not what this function is for.
     *
     * @param integer $columns The number of columns to use for presentation
     * @param mixed $filter A valid filter for \MUtil\Model\ModelAbstract->load()
     * @param mixed $sort A valid sort for \MUtil\Model\ModelAbstract->load()
     * @return \MUtil\Html\TableElement
     */
    public function getShowTable($columns = 1, $filter = null, $sort = null)
    {
        $model  = $this->getModel();
        $bridge = $model->getBridgeFor('itemTable');
        $bridge->setColumnCount($columns);

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        $table = $bridge->getTable();
        $table->class = 'displayer table';

        return $table;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('database object', 'database objects', $count);
    }

    /**
     *
     * @param string $title
     * @param string $question
     * @param string $info
     * @return boolean
     */
    public function isConfirmedItem($title, $question = null, $info = null)
    {
        if ($this->_getParam('confirmed')) {
            return true;
        }

        if (null === $question) {
            $question = $this->_('Are you sure?');
        }

        $this->html->h3(sprintf($title, $this->getTopic()));

        if ($info) {
            $this->html->pInfo($info);
        }

        $model    = $this->getModel();
        $repeater = $model->applyRequest($this->getRequest())->loadRepeatable();
        $table    = $this->getShowTable();
        $table->caption($question);
        $table->setRepeater($repeater);

        $footer = $table->tfrow($question, ' ', array('class' => 'centerAlign'));
        $footer->actionLink(array('confirmed' => 1), $this->_('Yes'), array('class' => 'btn-success'));
        $footer->actionLink(array('action' => 'show', 'class' => 'btn-warning'), $this->_('No'), array('class' => 'btn-danger'));

        $this->html[] = $table;
        $this->html->buttonDiv($this->createMenuLinks());

        return false;
    }

    public function patchAction()
    {
        $this->html->h3($this->_('Patch maintenance'));

        $patcher  = new \Gems\Util\DatabasePatcher($this->db, 'patches.sql', $this->escort->getDatabasePaths(), $this->project->databaseFileEncoding);
        $tableSql = sprintf(
            'SELECT gpa_level AS `%s`, gpa_location AS `%s`, COUNT(*) AS `%s`, COUNT(*) - SUM(gpa_executed) AS `%s`, SUM(gpa_executed) AS `%s`, SUM(gpa_completed) AS `%s`, MAX(gpa_changed) AS `%s` FROM gems__patches GROUP BY gpa_level, gpa_location ORDER BY gpa_level DESC, gpa_location',
            $this->_('Level'),
            $this->_('Group'),
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
        $level->addValidator(new \Zend_Validate_Digits());
        $form->addElement($level);

        $form->addElement($form->createElement('checkbox', 'completed', array('label' => $this->_('Ignore finished'))));
        $form->addElement($form->createElement('checkbox', 'executed', array('label' => $this->_('Ignore executed'))));
        $form->addElement($form->createElement('submit', 'show_button',   array('label' => $this->_('Show patches'), 'class' => 'button')));
        // $execute = new \Zend_Form_Element_Submit('save_button',   array('label' => $this->_('Execute'), 'class' => 'button'));
        // $form->addElement($execute);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            if ($form->isValid($data)) {
                $batch = $this->loader->getTaskRunnerBatch(__CLASS__ . $data['level']);
                $batch->setFormId($form->getId());
                if (! $batch->isLoaded()) {
                    $patcher->loadPatchBatch($data['level'], $data['completed'], $data['executed'], $batch);
                }

                $this->_helper->batchRunner(
                        $batch,
                        sprintf($this->_('Executing patch level %d'), $data['level']),
                        $this->accesslog
                        );

                $data['db_level'] = $data['level'];
                $form->getElement('db_level')->setValue($data['db_level']);

                $tableSql = sprintf(
                    'SELECT gpa_id_patch AS `%s`, gpa_level AS `%s`, gpa_location AS `%s`, gpa_name AS `%s`, gpa_sql AS `%s`, gpa_executed AS `%s`, gpa_completed AS `%s`, gpa_result AS `%s`, gpa_changed AS `%s` FROM gems__patches WHERE gpa_level = ? ORDER BY gpa_level, gpa_changed DESC, gpa_location, gpa_name, gpa_order',
                    $this->_('Patch'),
                    $this->_('Level'),
                    $this->_('Group'),
                    $this->_('Name'),
                    $this->_('Query'),
                    $this->_('Executed'),
                    $this->_('Finished'),
                    $this->_('Result'),
                    $this->_('Changed on'));

                $tableSql = $this->db->quoteInto($tableSql, $data['level']);

                // Hide the form: it is needed for the batch post, but we do not want it visible
                $form->setAttrib('style', 'display: none;');

                if ($this->getMessenger()->getCurrentMessages()) {
                    $this->accesslog->logChange($this->getRequest());
                }
            }

        } else {
            $changed = $patcher->uploadPatches($this->loader->getVersions()->getBuild());
            if ($changed == -1) {
                $this->addMessage($this->_('Create the patch table!'));
            } elseif ($changed) {
                $this->addMessage(sprintf($this->_('%d new or changed patch(es).'), $changed));
                $this->cache->invalidateTags(['sess_' . session_id()]);
            }

            $data['app_level'] = $this->loader->getVersions()->getBuild();
            $data['db_level']  = $this->db->fetchOne('SELECT gpl_level FROM gems__patch_levels ORDER BY gpl_level DESC');
            $data['level']     = min($data['db_level'] + 1, $data['app_level']);
            $data['completed'] = 1;
            $data['executed']  = 0;

            $form->populate($data);
        }

        $this->html[] = $form;

        if ($data = $this->db->fetchAll($tableSql)) {
            $table = \MUtil\Html\TableElement::createArray($data, $this->_('Patch overview'), true);
            $table->class = 'browser table table-striped table-bordered table-hover table-condensed';
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

                $rows = $this->db->fetchAll($sql, array(), \Zend_Db::FETCH_NUM);

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
            $this->accesslog->logChange($this->getRequest());

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
                $objCount  = 1;
                foreach ($objects as $data) {

                    $result = $model->runScript($data);
                    $results = array_merge($results, $result);
                    $results[] = sprintf($this->_('Finished %s creation script for object %d of %d'), $this->_(strtolower($data['type'])), $objCount, $oCount);
                    $objCount++;
                }
                $this->accesslog->logChange($this->_request, $results);

            } else {
                $results[] = $this->_('All objects exist. Nothing was executed.');
            }
            $this->addMessage($results);
            $this->accesslog->logChange($this->getRequest());
            $this->_cleanCache();
            $this->_reroute(array('action' => 'index'), true);
        }

        $this->html->h3($this->_('Create not-existing database objects'));
        if ($objects) {
            $this->html->pInfo(sprintf($this->plural('One database object does not exist.', 'These %d database objects do not exist.', $oCount), $oCount));
            $this->html->h4($this->plural('Are you sure you want to create it?', 'Are you sure you want to create them all?', $oCount));

            $model->set('name', 'itemDisplay', array(__CLASS__, 'createShowLink'), 'tableDisplay', 'em');
            $bridge = $model->getBridgeFor('table', array('class' => 'browser table'));
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
        $params = [
            'model' => $this->getModel(),
            'menuLinks' => $this->createMenuLinks(),
        ];
        $this->addSnippet('Database\\RunSqlFormSnippet', $params);
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

    /**
     * Show the changes in the database
     */
    public function showChangesAction()
    {
        $patchLevels = $this->db->fetchPairs('SELECT DISTINCT gpa_level, gpa_level FROM gems__patches ORDER BY gpa_level DESC');

        $searchData['gpa_level'] = reset($patchLevels);

        if ($this->request instanceof \Zend_Controller_Request_Abstract) {
            $searchData = $this->request->getParams() + $searchData;
        }

        $snippet = $this->addSnippet(
                'Database\\StructuralChanges',
                'patchLevels', $patchLevels,
                'searchData', $searchData
                );

        if (1 == $this->request->getParam('download')) {
            $snippet->outputText($this->view, $this->_helper);
        }
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
            $caption = sprintf($this->_('Contents of %s %s'), $this->_($data['type']), $data['name']);
            $this->html->h3(sprintf($this->_('The data in table %s'), $data['name']));
            $this->createDataTable($data, $caption);
            $this->html[] = $this->createMenuLinks();
        }
    }
}
