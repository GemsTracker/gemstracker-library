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
 * @version    $Id: DbaModel.php 345 2011-07-28 08:39:24Z 175780 $
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

    private $_sorts;

    public function __construct(Zend_Db_Adapter_Abstract $db, $mainDirectory, $directory_2 = null)
    {
        parent::__construct($mainDirectory);

        $this->mainDirectory = $mainDirectory;
        $this->directories   = MUtil_Ra::args(func_get_args(), 1);
        $this->setLocations();

        $this->db = $db;

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

    private function _loadAllData()
    {
        $tables = $this->db->listTables();
        if ($tables) { // Can be empty
            $tables = array_change_key_case(array_combine($tables, $tables), CASE_LOWER);
        }

        $data  = array();

        foreach (array_reverse($this->directories) as $i => $mainDirectory) {
            $location = $this->locations[$i];

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

    public function load($filter = true, $sort = true)
    {
        $data = $this->_loadAllData();

        if ($filter) {
            $data = $this->_filterData($data, $this->_checkFilterUsed($filter));
        }

        if ($sort) {
            $data = $this->_sortData($data, $this->_checkSortUsed($sort));
        }

        return $data;
    }

    public function loadTable($tableName)
    {
        return $this->loadFirst(array('name' => $tableName), false);
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
