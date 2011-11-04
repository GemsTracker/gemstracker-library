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
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * File for checking and executing (new) patches.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Util_DatabasePatcher
{
    private $_loaded_patches;

    protected $db;
    protected $patch_files;
    protected $patch_locations;

    public function __construct(Zend_Db_Adapter_Abstract $db, $files, $paths = null)
    {
        $this->db = $db;

        if (! is_array($paths)) {
            $paths = (array) $paths;
        }

        foreach ((array) $files as $file) {
            if (file_exists($file)) {
                $this->patch_files[] = $file;

            } elseif ($paths) {
                foreach ($paths as $location => $path) {
                    if (file_exists($path . '/' . $file)) {
                        $this->patch_files[] = $path . '/' . $file;

                        if (! is_numeric($location)) {
                            $this->patch_locations[$path . '/' . $file] = $location;
                        }
                    }
                }
            }
        }
    }

    private function _loadPatches($applicationLevel)
    {
        if (! $this->_loaded_patches) {
            $this->_loaded_patches = array();

            foreach ($this->patch_files as $file) {
                $this->_loadPatchFile($file, $applicationLevel);
            }
        }
    }

    private function _loadPatchFile($file_name, $applicationLevel)
    {
        if (isset($this->patch_locations[$file_name])) {
            $location = $this->patch_locations[$file_name];
        } else {
            $location = $file_name;
        }

        if ($sql = file_get_contents($file_name)) {

            $levels = preg_split('/--\s*(GEMS\s+)?VERSION:?\s*/', $sql);

            // SQL before first -- VERSION: is ignored.
            array_shift($levels);

            foreach ($levels as $level) {
                list($levelnrtext, $leveltext) = explode("\n", $level, 2);

                $levelnr = intval($levelnrtext);
                if ($levelnr && ($levelnr <= $applicationLevel)) {

                    $patches = preg_split('/--\s*PATCH:?\s*/', $leveltext);

                    // SQL before first -- PATCH: is ignored.
                    array_shift($patches);

                    foreach ($patches as $patch) {
                        // First line now contains patch name
                        list($name, $statements) = explode("\n", $patch, 2);

                        $name = substr(trim($name), 0, 30);

                        // MUtil_Echo::r($statements, $name);
                        foreach (MUtil_Parser_Sql_WordsParser::splitStatements($statements, false) as $i => $statement) {
                            $this->_loaded_patches[] = array(
                                    'gpa_level'    => $levelnr,
                                    'gpa_location' => $location,
                                    'gpa_name'     => $name,
                                    'gpa_order'    => $i,
                                    'gpa_sql'      => $statement
                                );
                        }
                    }

                }
            }
        }
    }

    /**
     * Executes db patches for the given $patchLevel
     *
     * @param int $patchLevel Only execute patches for this patchlevel
     * @param boolean $ignoreCompleted Set to yes to skip patches that where already completed
     * @param boolean $ignoreExecuted Set to yes to skip patches that where already executed (this includes the ones that are executed but not completed)
     * @return int The number of executed patches
     */
    public function executePatch($patchLevel, $ignoreCompleted = true, $ignoreExecuted = false)
    {
        $sql = 'SELECT gpa_id_patch, gpa_sql, gpa_completed FROM gems__patches WHERE gpa_level = ?';
        if ($ignoreCompleted) {
            $sql .= ' AND gpa_completed = 0';
        }
        if ($ignoreExecuted) {
            $sql .= ' AND gpa_executed = 0';
        }
        $sql .= ' ORDER BY gpa_level, gpa_location, gpa_name, gpa_order';
        // MUtil_Echo::rs($ignoreCompleted, $ignoreExecuted, $sql);

        $current  = new Zend_Db_Expr('CURRENT_TIMESTAMP');
        $executed = 0;
        $patches  = $this->db->fetchAll($sql, $patchLevel);

        foreach ($patches as $patch) {
            $data = array();
            $data['gpa_executed'] = 1;
            $data['gpa_changed']  = $current;

            try {
                $stmt = $this->db->query($patch['gpa_sql']);
                if ($rows = $stmt->rowCount()) {
                    $data['gpa_result'] = 'OK: ' . $rows . ' changed';
                } else {
                    $data['gpa_result'] = 'OK';
                }
                $data['gpa_completed'] = 1;

            } catch (Zend_Db_Statement_Exception $e) {
                $data['gpa_result'] = substr($e->getMessage(), 0, 254);
                $data['gpa_completed'] = $patch['gpa_completed'] ? $patch['gpa_completed'] : 0;
            }

            $this->db->update('gems__patches', $data, $this->db->quoteInto('gpa_id_patch = ?', $patch['gpa_id_patch']));
            $executed++;
        }

        //Update the patchlevel only when we have executed at least one patch
        if ($executed>0) {
            $this->db->query('INSERT IGNORE INTO gems__patch_levels (gpl_level, gpl_created) VALUES (?, CURRENT_TIMESTAMP)', $patchLevel);
        }

        return $executed;
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

    public function hasPatchFiles()
    {
        return (boolean) $this->patch_files;
    }

    public function uploadPatches($applicationLevel)
    {
        // Load current
        $select = $this->db->select();
        $select->from('gems__patches', array('gpa_level', 'gpa_location', 'gpa_name', 'gpa_order', 'gpa_sql', 'gpa_id_patch'));

        try {
            $existing = $select->query()->fetchAll();
        } catch (exception $e) {
            return -1;
        }

        $tree    = MUtil_Ra_Nested::toTree($existing, 'gpa_level', 'gpa_location', 'gpa_name', 'gpa_order');
        $changed = 0;
        $current = new Zend_Db_Expr('CURRENT_TIMESTAMP');
        $minimum = $this->getMinimumPatchLevel();
        // MUtil_Echo::track($minimum);

        $this->_loadPatches($applicationLevel);
        foreach ($this->_loaded_patches as $patch) {
            if ($minimum <= $patch['gpa_level']) {
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

                        $this->db->update('gems__patches', $values, $this->db->quoteInto('gpa_id_patch = ?', $tree[$level][$location][$name][$order]['gpa_id_patch']));
                        $changed++;
                    }

                } else {
                    $patch['gpa_changed'] = $current;
                    $patch['gpa_created'] = $current;
                    $this->db->insert('gems__patches', $patch);
                    $changed++;
                }
            }
        } // */

        return $changed;
    }
}
