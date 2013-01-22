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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Database Administration model. This model reads data about the database
 * structure both from the file system (configs/db*) and the database
 * and shows a combination of the actual database structure and required
 * database structure.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_DbaModel extends MUtil_Model_ModelAbstract
{
    const DEFAULT_ORDER = 1000;

    const STATE_CREATED = 1;
    const STATE_DEFINED = 2;
    const STATE_UNKNOWN = 3;

    protected $db;
    protected $directories;
    protected $file_encoding;
    protected $locations;
    protected $mainDirectory;
    /**
     * @var Zend_Translate_Adapter
     */
    protected $translate;

    private $_sorts;

    public function __construct(Zend_Db_Adapter_Abstract $db, $mainDirectory, $directory_2 = null)
    {
        parent::__construct($mainDirectory);

        $this->mainDirectory = $mainDirectory;
        $this->directories   = MUtil_Ra::args(func_get_args(), 1);
        $this->setLocations();

        $this->db = $db;

        //Grab translate object from the Escort
        $this->translate = GemsEscort::getInstance()->translate;

        $this->set('group',       'maxlength', 40, 'type', MUtil_Model::TYPE_STRING);
        $this->set('name',        'key', true, 'maxlength', 40, 'type', MUtil_Model::TYPE_STRING);
        $this->set('type',        'maxlength', 40, 'type', MUtil_Model::TYPE_STRING);
        $this->set('order',       'decimals', 0, 'default', self::DEFAULT_ORDER, 'maxlength', 6, 'type', MUtil_Model::TYPE_NUMERIC);
        $this->set('defined',     'type', MUtil_Model::TYPE_NUMERIC);
        $this->set('exists',      'type', MUtil_Model::TYPE_NUMERIC);
        $this->set('state',       'type', MUtil_Model::TYPE_NUMERIC);
        $this->set('path',        'maxlength', 255, 'type', MUtil_Model::TYPE_STRING);
        $this->set('fullPath',    'maxlength', 255, 'type', MUtil_Model::TYPE_STRING);
        $this->set('fileName',    'maxlength', 100, 'type', MUtil_Model::TYPE_STRING);
        $this->set('script',      'type', MUtil_Model::TYPE_STRING);
        $this->set('lastChanged', 'type', MUtil_Model::TYPE_DATETIME);
        $this->set('location',    'maxlength', 12, 'type', MUtil_Model::TYPE_STRING);
        $this->set('state',       'multiOptions', array(
            Gems_Model_DbaModel::STATE_CREATED => $this->_('created'),
            Gems_Model_DbaModel::STATE_DEFINED => $this->_('not created'),
            Gems_Model_DbaModel::STATE_UNKNOWN => $this->_('unknown')));
    }

    /**
     * proxy for easy access to translations
     *
     * @param  string             $messageId Translation string
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with locale
     *                                       identifier, @see Zend_Locale for more information
     * @return string
     */
    private function _($messageId, $locale = null)
    {
        return $this->translate->_($messageId, $locale);
    }

    private function _getGroupName($name)
    {
        if ($pos = strpos($name,  '__')) {
            return substr($name, 0,  $pos);
        }

        return null;
    }

    private function _getOrder(&$name)
    {
        if ($pos = strrpos($name,  '.')) {
            $order = substr($name, $pos + 1);
            if (is_numeric($order)) {
                $name = substr($name, 0,  $pos);

                return $order;
            }
        }

        return self::DEFAULT_ORDER;
    }

    private function _getType($name)
    {
        if (substr($name, -1) == 's') {
            return substr($name,  0,  -1);
        } else {
            return $name;
        }
    }

    /**
     * Returns a nested array containing the items requested.
     *
     * @param array $filter Filter array, num keys contain fixed expresions, text keys are equal or one of filters
     * @param array $sort Sort array field name => sort type
     * @return array Nested array or false
     */
    protected function _load(array $filter, array $sort)
    {
        $data = $this->_loadAllData();

        if ($filter) {
            $data = $this->_filterData($data, $filter);
        }

        if ($sort) {
            $data = $this->_sortData($data, $sort);
        }

        return $data;
    }

    private function _loadAllData()
    {
        $tables = $this->db->listTables();
        if ($tables) { // Can be empty
            $tables = array_change_key_case(array_combine($tables, $tables), CASE_LOWER);
        }

        $data  = array();

        foreach (array_reverse($this->directories) as $i => $mainDirectory) {
            $location = $this->locations[$i];

            if (is_dir($mainDirectory)) {
                foreach (new DirectoryIterator($mainDirectory) as $directory) {
                    $type = $this->_getType($directory->getFilename());

                    if ($directory->isDir() && (! $directory->isDot())) {
                        $path = $directory->getPathname();

                        foreach (new DirectoryIterator($path) as $file) {

                            $fileName = strtolower($file->getFilename());

                            if (substr($fileName, -4) == '.sql') {
                                $fileName = substr($fileName,  0,  -4);
                                $forder   = $this->_getOrder($fileName); // Changes $fileName

                                if ($fexists = array_key_exists($fileName, $tables)) {
                                    unset($tables[$fileName]);
                                } elseif (array_key_exists($fileName, $data)) {
                                    // $fexists is also true when the table was already defined
                                    // in a previous directory
                                    $fexists = $data[$fileName]['exists'];
                                }

                                $fileContent = file_get_contents($file->getPathname());
                                if ($this->file_encoding) {
                                    $fileContent = mb_convert_encoding($fileContent, mb_internal_encoding(), $this->file_encoding);
                                }

                                $data[$fileName] = array(
                                    'name'        => $fileName,
                                    'group'       => $this->_getGroupName($fileName),
                                    'type'        => $type,
                                    'order'       => $forder,
                                    'defined'     => true,
                                    'exists'      => $fexists,
                                    'state'       => $fexists ? self::STATE_CREATED : self::STATE_DEFINED,
                                    'path'        => $path,
                                    'fullPath'    => $file->getPathname(),
                                    'fileName'    => $file->getFilename(),
                                    // MUtil_Lazy does not serialize
                                    // 'script'      => MUtil_Lazy::call('file_get_contents', $file->getPathname()),
                                    'script'      => $fileContent,
                                    'lastChanged' => $file->getMTime(),
                                    'location'    => $location,
                                    );
                            }
                        }
                    }
                }
            }
        }

        foreach ($tables as $table) {
            $data[$table] = array(
                'name'        => $table,
                'group'       => $this->_getGroupName($table),
                'type'        => 'table',
                'order'       => self::DEFAULT_ORDER,
                'defined'     => false,
                'exists'      => true,
                'state'       => self::STATE_UNKNOWN,
                'path'        => null,
                'fullPath'    => $file->getPathname(),
                'fileName'    => $table . '.' . self::STATE_UNKNOWN . '.sql',
                'script'      => '',
                'lastChanged' => null,
                'location'    => 'n/a',
                );
        }
        return $data;
    }

    private function _applyFiltersToRow(array $row, array $filters, $logicalAnd)
    {
        foreach ($filters as $name => $filter) {
            if (is_numeric($name)) {
                $value = $row;
            } else {
                $value = isset($row[$name]) ? $row[$name] : null;
            }

            if (is_callable($filter)) {
                $result = call_user_func($filter, $value);
            } elseif (is_array($filter)) {
                $result = $this->_applyFilter($value, $filter, ! $logicalAnd);
            } else {
                $result = $value === $filter;
                // MUtil_Echo::r($value . '===' . $filter . '=' . $result);
            }

            // if ($logicalAnd xor $result) {
            if (! $result) {
                return $result;
            }
        }

        // If $logicalAnd is true:
        //   => all filters must have triggered true to arrive here
        //   => the result is true,
        // If $logicalAnd is false:
        //   => all filters must have triggered false to arrive here
        //   => the result is false.
        return $logicalAnd;
    }

    private function _filterData(array $data, array $filters)
    {
        $filteredData = array();

        foreach ($data as $key => $row) {
            if ($this->_applyFiltersToRow($row, $filters, true)) {
                // print_r($row);
                $filteredData[$key] = $row;
            }
        }

        return $filteredData;
    }

    private function _sortData(array $data, $sorts)
    {
        $this->_sorts = array();

        foreach ($sorts as $key => $order) {
            if (is_numeric($key) || is_string($order)) {
                if (strtoupper(substr($order,  -5)) == ' DESC') {
                    $order     = substr($order,  0,  -5);
                    $direction = SORT_DESC;
                } else {
                    if (strtoupper(substr($order,  -4)) == ' ASC') {
                        $order = substr($order,  0,  -4);
                    }
                    $direction = SORT_ASC;
                }
                $this->_sorts[$order] = $direction;

            } else {
                switch ($order) {
                    case SORT_DESC:
                        $this->_sorts[$key] = SORT_DESC;
                        break;

                    case SORT_ASC:
                    default:
                        $this->_sorts[$key] = SORT_ASC;
                        break;
                }
            }
        }

        usort($data, array($this, 'cmp'));

        return $data;
    }

    public function cmp(array $a, array $b)
    {
        foreach ($this->_sorts as $key => $direction) {
            if ($a[$key] !== $b[$key]) {
                // MUtil_Echo::r($key . ': [' . $direction . ']' . $a[$key] . '-' . $b[$key]);
                if (SORT_ASC == $direction) {
                    return $a[$key] > $b[$key] ? 1 : -1;
                } else {
                    return $a[$key] > $b[$key] ? -1 : 1;
                }
            }
        }

        return 0;
    }

    public function delete($filter = true)
    {
        // TODO: implement
    }

    public function getFileEncoding()
    {
        return $this->file_encoding;
    }

    public function getTextSearchFilter($searchText)
    {
        $filter = array();

        if ($searchText) {
            $fields = array();
            foreach ($this->getItemNames() as $name) {
                // TODO: multiOptions integratie
                if ($this->get($name, 'label') && (! $this->get($name, 'multiOptions'))) {
                    $fields[] = $name;
                }
            }

            if ($fields) {
                foreach ($this->getTextSearches($searchText) as $searchOn) {
                    $filterItem = new MUtil_Ra_Filter_Contains($searchOn, $fields);
                    $filter[] = array($filterItem, 'filter');
                }
            }
        }

        return $filter;
    }

    public function hasNew()
    {
        return false;
    }

    public function hasTextSearchFilter()
    {
        return true;
    }

    public function loadTable($tableName)
    {
        return $this->loadFirst(array('name' => $tableName), false);
    }

    /**
     * Run a sql statement from an object loaded through this model
     *
     * $data is an array with the following keys:
     * script   The sql statement to be executed
     * name     The name of the table, used in messages
     * type     Type of db element (table or view), used in messages
     *
     * @param array $data
     * @param type $includeResultSets
     * @return type
     */
    public function runScript(array $data, $includeResultSets = false)
    {
        $results = array();
        if ($data['script']) {
            $queries = MUtil_Parser_Sql_WordsParser::splitStatements($data['script'], false);
            $qCount  = count($queries);

            $results[] = sprintf($this->_('Executed %2$s creation script %1$s:'), $data['name'], $this->_(strtolower($data['type'])));
            $i = 1;
            $resultSet = 1;

            foreach ($queries as $query) {
                $sql = (string) $query;
                try {
                    $stmt = $this->db->query($sql);
                    if ($rows = $stmt->rowCount()) {
                        if ($includeResultSets && ($data = $stmt->fetchAll())) {
                            $results[] = sprintf($this->_('%d record(s) returned as result set %d in step %d of %d.'), $rows, $resultSet, $i, $qCount);
                            $results[] = $data;
                            $resultSet++;
                        } else {
                            $results[] = sprintf($this->_('%d record(s) updated in step %d of %d.'), $rows, $i, $qCount);
                        }
                    } else {
                        $results[] = sprintf($this->_('Script ran step %d of %d succesfully.'), $i, $qCount);
                    }
                } catch (Zend_Db_Statement_Exception $e) {
                    $results[] = $e->getMessage() . $this->_('  in step ') . $i . ':<pre>' . $sql . '</pre>';
                }
                $i++;
            }
        } else {
            $results[] = sprintf($this->_('No script for %1$s.'), $data['name']);
        }

        return $results;
    }

    public function save(array $newValues, array $filter = null)
    {
        // TODO: Save of data
    }

    public function setFileEncoding($encoding)
    {
        $this->file_encoding = $encoding;

        return $this;
    }

    public function setLocations($main = null, $loc1 = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        $i = 1;
        foreach ($this->directories as $key => $dir) {
            if (isset($args[$key])) {
                $locations[$key] = $args[$key];
            } else {
                $locations[$key] = '# ' . $i;
            }
            $i++;
        }

        $this->locations = array_reverse($locations);

        return $this;
    }
}
