<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RoundModel.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Model;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 21-apr-2015 13:43:07
 */
class RoundModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Construct a round model
     */
    public function __construct(\Zend_Db_Adapter_Abstract $db)
    {
        parent::__construct('rounds', 'gems__rounds', 'gro', true);

        $this->db = $db;
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        $this->trackUsage();
        $rows = $this->load($filter);

        if ($rows) {
            foreach ($rows as $row) {
                if (isset($row['gro_id_round'])) {
                    $roundId = $row['gro_id_round'];
                    if ($this->isDeleteable($roundId)) {
                        $this->db->delete('gems__rounds', $this->db->quoteInto('gro_id_round = ?', $roundId));

                        // Delete the round before anyone starts using it
                        $this->db->delete('gems__tokens', $this->db->quoteInto('gto_id_round = ?', $roundId));
                    } else {
                        $values['gro_id_round'] = $roundId;
                        $values['gro_active']   = 0;
                        $this->save($values);
                    }
                    $this->addChanged();
                }
            }
        }
    }

    /**
     * Get the number of times someone started answering this round.
     *
     * @param int $roundId
     * @return int
     */
    public function getStartCount($roundId)
    {
        if (! $roundId) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM gems__tokens WHERE gto_id_round = ? AND gto_start_time IS NOT NULL";
        return $this->db->fetchOne($sql, $roundId);
    }

    /**
     * Can this round be deleted as is?
     *
     * @param int $roundId
     * @return boolean
     */
    public function isDeleteable($roundId)
    {
        if (! $roundId) {
            return true;
        }
        $sql = "SELECT gto_id_token FROM gems__tokens WHERE gto_id_round = ? AND gto_start_time IS NOT NULL";
        return (boolean) ! $this->db->fetchOne($sql, $roundId);
    }
}
