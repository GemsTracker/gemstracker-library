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
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_Model_UserModel extends Gems_Model_JoinModel
{
    /**
     * The length of a user id.
     *
     * @var int
     */
    protected $userIdLen = 8;

    /**
     * Create a model that joins two or more tables
     *
     * @param string $name          The name of the model
     * @param string $secondTable   The optional second base table for the model
     * @param array  $joinFields    Array of source->dest primary keys for this join
     * @param string $fieldPrefix   Prefix to use for change fields (date/userid)
     * @param bool   $saveable      Will changes to this table be saved
     */
    public function __construct($name, $secondTable = null, array $joinFields = null, $fieldPrefix = null, $saveable = null)
    {
        parent::__construct($name, 'gems__users', (null === $saveable ? $fieldPrefix : $saveable));

        if ($fieldPrefix) {
            Gems_Model::setChangeFieldsByPrefix($this, 'gsu');
        }

        if ($secondTable) {
            $this->addTable($secondTable, $joinFields, $fieldPrefix, $saveable);
        }
    }

    /**
     * Finds a random unique user id.
     *
     * @return int
     */
    protected function _createUserId()
    {
        $db = $this->getAdapter();

        $max = $this->userIdLen;

        do {
            $out = mt_rand(1, 9);
            for ($i = 1; $i < $this->userIdLen; $i++) {
                $out .= mt_rand(0, 9);
            }
            // Make it a number
            $out = intval($out);

        } while ($db->fetchOne('SELECT gsu_id_user FROM gems__users WHERE gsu_id_user = ?', $out));

        return $out;
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        if (! (isset($newValues['gsu_id_user']) && $newValues['gsu_id_user'])) {
            // Load a new user id if needed
            $newValues['gsu_id_user'] = $this->_createUserId();
        }

        return parent::save($newValues, $filter, $saveTables);
    }
}
