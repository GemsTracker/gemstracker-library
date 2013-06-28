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
 * @copyright  Copyright (c) 201 Erasmus MC
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
class Gems_Model_DbaModel extends MUtil_Model_ArrayModelAbstract
{
    const DEFAULT_ORDER = 1000;

    const STATE_CREATED = 1;
    const STATE_DEFINED = 2;
    const STATE_UNKNOWN = 3;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $defaultDb;

    /**
     *
     * @var array 'path' => directory, 'db' => Zend_Db_Adapter_Abstract, 'name' => name
     */
    protected $directories;

    /**
     * The encoding used to read files
     * @var string
     */
    protected $file_encoding;

    /**
     * @var Zend_Translate_Adapter
     */
    protected $translate;

    /**
     *
     * @param Zend_Db_Adapter_Abstract $db
     * @param array $directories directory => name | Zend_Db_Adaptor_Abstract | array(['path' =>], 'name' =>, 'db' =>,)
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, array $directories)
    {
        parent::__construct('DbaModel');

        $this->defaultDb = $db;

        foreach ($directories as $path => $value) {
            $this->addDirectory($path, $value);
        }

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
        if ($this->translate) {
            return $this->translate->_($messageId, $locale);
        }

        return $messageId;
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
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return Traversable Return an iterator over or an array of all the rows in this object
     */
    protected function _loadAllTraversable()
    {
        $tables = array();
        foreach (array_reverse($this->directories) as $pathData) {
            $tables = $tables + $pathData['db']->listTables();
        }
        if ($tables) { // Can be empty
            $tables = array_change_key_case(array_combine($tables, $tables), CASE_LOWER);
        }

        $data  = array();

        foreach (array_reverse($this->directories) as $i => $pathData) {
            $mainDirectory = $pathData['path'];
            $location      = $pathData['name'];
            $db            = $pathData['db'];

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
                                if ($this->file_encoding && ($this->file_encoding !== mb_internal_encoding())) {
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
                                    'db'          => $db,
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

    /**
     * Add a directory with definitions to list of directories
     *
     * @param string $path
     * @param mixed name | Zend_Db_Adaptor_Abstract | array(['path' =>], ['name' =>, |'db' =>,])
     * @return \Gems_Model_DbaModel
     */
    public function addDirectory($path, $value)
    {
        if (is_array($value)) {
            $pathData = $value;

        } elseif ($value instanceof Zend_Db_Adapter_Abstract) {
            $pathData['db'] = $value;

        } else {
            $pathData['name'] = $value;
        }

        if (! isset($pathData['path'])) {
            $pathData['path'] = $path;
        }
        if (! isset($pathData['db'])) {
            $pathData['db'] = $this->defaultDb;
        }
        if (! isset($pathData['name'])) {
            $config = $pathData['db']->getConfig();
            $pathData['name'] = $config['dbname'];
        }

        $this->directories[] = $pathData;

        return $this;
    }

    public function getFileEncoding()
    {
        return $this->file_encoding;
    }

    /**
     * Quick filter alias function for loading  a single table
     *
     * @param string $tableName
     * @return array
     */
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
        if (! isset($data['db'])) {
            $data['db'] = $this->defaultDb;
        }
        if ($data['script']) {
            $queries = MUtil_Parser_Sql_WordsParser::splitStatements($data['script'], false);
            $qCount  = count($queries);

            $results[] = sprintf($this->_('Executed %2$s creation script %1$s:'), $data['name'], $this->_(strtolower($data['type'])));
            $i = 1;
            $resultSet = 1;

            foreach ($queries as $query) {
                $sql = (string) $query;
                try {
                    $stmt = $data['db']->query($sql);
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

    /**
     * Set the text encoding of the db definition files
     *
     * @param string $encoding
     * @return \Gems_Model_DbaModel (continuation pattern)
     */
    public function setFileEncoding($encoding)
    {
        $this->file_encoding = $encoding;

        return $this;
    }
}