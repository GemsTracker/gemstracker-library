<?php

/**
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

/**
 * File for checking and executing (new) patches.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class DatabasePatcher
{
    /**
     *
     * @var array
     */
    private $_loaded_patches;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var string
     */
    protected $encoding;


    /**
     *
     * @var array Of location => \Zend_Db_Adapter_Abstract db
     */
    protected $patch_databases;

    /**
     *
     * @var array Of file => location
     */
    protected $patch_sources;

    /**
     *
     * @param \Zend_Db_Adapter_Abstract $db
     * @param mixed $files Array of file names or single file name
     * @param array $databases Nested array with rowes containing path, name and db keys.
     */
    public function __construct(\Zend_Db_Adapter_Abstract $db, $files, array $databases, $encoding = null)
    {
        $this->db = $db;
        $this->encoding = $encoding;

        foreach ((array) $files as $file) {
            foreach ($databases as $dbData) {
                if (file_exists($dbData['path'] . '/' . $file)) {
                    $this->patch_databases[$dbData['name']] = $dbData['db'];
                    $this->patch_sources[$dbData['path'] . '/' . $file] = $dbData['name'];
                }
            }
        }
    }

    /**
     * Load all patches from all patch files with the range
     *
     * @param int $minimumLevel
     * @param int $maximumLevel
     */
    private function _loadPatches($minimumLevel, $maximumLevel)
    {
        if (! $this->_loaded_patches) {
            $this->_loaded_patches = array();

            foreach ($this->patch_sources as $file => $location) {
                $this->_loadPatchFile($file, $location, $minimumLevel, $maximumLevel);
            }
        }
    }

    /**
     * Load all patches from a single patch file
     *
     * @param string $file Full filename
     * @param string $location Location description
     * @param int $minimumLevel
     * @param int $maximumLevel
     */
    private function _loadPatchFile($file, $location, $minimumLevel, $maximumLevel)
    {
        if ($sql = file_get_contents($file)) {
            if ($this->encoding && ($this->encoding !== mb_internal_encoding())) {
                $sql = mb_convert_encoding($sql, mb_internal_encoding(), $this->encoding);
            }

            $levels = preg_split('/--\s*(GEMS\s+)?VERSION:?\s*/', $sql);

            // SQL before first -- VERSION: is ignored.
            array_shift($levels);

            foreach ($levels as $level) {
                list($levelnrtext, $leveltext) = explode("\n", $level, 2);

                // no loading of unused patches
                $levelnr = intval($levelnrtext);
                if ($levelnr && ($levelnr >= $minimumLevel) && ($levelnr <= $maximumLevel)) {

                    $patches = preg_split('/--\s*PATCH:?\s*/', $leveltext);

                    // SQL before first -- PATCH: is ignored.
                    array_shift($patches);

                    foreach ($patches as $patch) {
                        // First line now contains patch name
                        list($name, $statements) = explode("\n", $patch, 2);

                        $name = substr(trim($name), 0, 30);

                        // \MUtil\EchoOut\EchoOut::r($statements, $name);
                        foreach (\MUtil\Parser\Sql\WordsParser::splitStatements($statements, false) as $i => $statement) {
                            $this->_loaded_patches[] = array(
                                    'gpa_level'    => $levelnr,
                                    'gpa_location' => $location,
                                    'gpa_name'     => $name,
                                    'gpa_order'    => $i,
                                    'gpa_sql'      => $statement,
                                );
                        }
                    }

                }
            }
        }
    }

    /**
     * New installations should not be trequired to run patches. This esthablishes that level.
     *
     * @return int The lowest level of patch stored in the database.
     */
    protected function getMinimumPatchLevel()
    {
        static $level;

        if (! $level) {
            $level = intval($this->db->fetchOne("SELECT COALESCE(MIN(gpl_level), 1) FROM gems__patch_levels"));
        }

        return $level;
    }

    /**
     * Get the database for a location
     *
     * @param string $location
     * @return \Zend_Db_Adapter_Abstract
     */
    public function getPatchDatabase($location)
    {
        if (isset($this->patch_databases[$location])) {
            return $this->patch_databases[$location];
        }

        return $this->db;
    }

    /**
     * There exist files with patches to load
     * @return boolean
     */
    public function hasPatchFiles()
    {
        return (boolean) $this->patch_files;
    }

    /**
     * Loads execution of selected db patches for the given $patchLevel into a TaskBatch.
     *
     * @param int $patchLevel Only execute patches for this patchlevel
     * @param boolean $ignoreCompleted Set to yes to skip patches that where already completed
     * @param boolean $ignoreExecuted Set to yes to skip patches that where already executed
     *                                (this includes the ones that are executed but not completed)
     * @param \MUtil\Task\TaskBatch $batch Optional batch, otherwise one is created
     * @return \MUtil\Task\TaskBatch The batch
     */
    public function loadPatchBatch($patchLevel, $ignoreCompleted, $ignoreExecuted, \MUtil\Task\TaskBatch $batch)
    {
        $select = $this->db->select();
        $select->from('gems__patches', array('gpa_id_patch', 'gpa_sql', 'gpa_location', 'gpa_completed'))
                ->where('gpa_level = ?', $patchLevel)
                ->order('gpa_level')
                ->order('gpa_location')
                ->order('gpa_id_patch');

        if ($ignoreCompleted) {
            $select->where('gpa_completed = 0');
        }
        if ($ignoreExecuted) {
            $select->where('gpa_executed = 0');
        }
        // \MUtil\EchoOut\EchoOut::track($ignoreCompleted, $ignoreExecuted, $select);

        $executed = 0;
        $patches  = $select->query()->fetchAll();

        if ($patches) {
            foreach ($patches as $patch) {
                $batch->addTask(
                        'Db\\ExecuteOnePatch',
                        $patch['gpa_location'],
                        $patch['gpa_sql'],
                        $patch['gpa_completed'],
                        $patch['gpa_id_patch']
                        );
            }

            $batch->addTask('Db\\UpdatePatchLevel', $patchLevel);
            $batch->addTask('CleanCache');
        }

        return $batch;
    }

    /**
     * Load all (new and changed) patches from all patch files into permanent storage in the database
     *
     * @param int $applicationLevel Highest level of patches to load (no loading of future patches)
     */
    public function uploadPatches($applicationLevel)
    {
        // Load current
        $select = $this->db->select();
        $select->from(
                'gems__patches',
                array('gpa_level', 'gpa_location', 'gpa_name', 'gpa_order', 'gpa_sql', 'gpa_id_patch')
                );

        try {
            $existing = $select->query()->fetchAll();
        } catch (exception $e) {
            return -1;
        }

        // Change into a nested tree for easy access
        $tree    = \MUtil\Ra\Nested::toTree($existing, 'gpa_level', 'gpa_location', 'gpa_name', 'gpa_order');
        $changed = 0;
        $current = new \MUtil\Db\Expr\CurrentTimestamp();

        $this->_loadPatches($this->getMinimumPatchLevel(), $applicationLevel);
        // \MUtil\EchoOut\EchoOut::track($this->_loaded_patches);
        foreach ($this->_loaded_patches as $patch) {
            $level    = $patch['gpa_level'];
            $location = $patch['gpa_location'];
            $name     = $patch['gpa_name'];
            $order    = $patch['gpa_order'];

            // Does it exist?
            if (isset($tree[$level][$location][$name][$order])) {
                $sql = $patch['gpa_sql'];
                if ($sql != $tree[$level][$location][$name][$order]['gpa_sql']) {
                    $values['gpa_sql']       = $sql;
                    $values['gpa_executed']  = 0;
                    $values['gpa_completed'] = 0;
                    $values['gpa_changed']   = $current;

                    $where = $this->db->quoteInto(
                            'gpa_id_patch = ?',
                            $tree[$level][$location][$name][$order]['gpa_id_patch']
                            );

                    // \MUtil\EchoOut\EchoOut::track($values, $where);
                    $this->db->update('gems__patches', $values, $where);
                    $changed++;
                }

            } else {
                $patch['gpa_changed'] = $current;
                $patch['gpa_created'] = $current;
                $this->db->insert('gems__patches', $patch);
                $changed++;
            }
        } // */

        return $changed;
    }
}
