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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extension of the Standard Token Model that implements extra
 * functions for saving the data for single survey engine tracks.
 *
 * @see Gems_Tracker_Engine_SingleSurveyEngine
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Model_SingleSurveyTokenModel extends Gems_Tracker_Model_StandardTokenModel
{
    /**
     *
     * @var boolean Set to true when data in the respondent2track table must be saved as well
     */
    protected $saveRespondentTracks = true;

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

    /**
     * The $tracker is needed to store new tokens
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     * General utility function for saving a row in a table, overridden
     * for the case a new token must be created.
     *
     * This functions checks for prior existence of the row and switches
     * between insert and update as needed. Key updates can be handled through
     * passing the $oldKeys or by using copyKeys().
     *
     * @see copyKeys()
     *
     * @param Zend_Db_Table_Abstract $table The table to save
     * @param array $newValues The values to save, including those for other tables
     * @param array $oldKeys The original keys as they where before the changes
     * @return array The values for this table as they were updated
     */
    protected function _saveTableData(Zend_Db_Table_Abstract $table, array $newValues,
            array $oldKeys = null, $saveMode = self::SAVE_MODE_ALL)
    {
        $table_name = $this->_getTableName($table);

        // Are we creating a new token?
        if (($table_name === 'gems__tokens') &&
                (! (isset($newValues['gto_id_token']) && $newValues['gto_id_token']))) {

            // Use the tracker function instead of the model function!
            $tokenData = $this->_filterDataFor($table_name, $newValues, true);
            $tokenData['gto_id_token'] = $this->tracker->createToken($tokenData, $this->session->user_id);

            return $tokenData;

        } else {
            return parent::_saveTableData($table, $newValues, $oldKeys, $saveMode);
        }
    }

    /**
     * Delete items from the model
     *
     * Contains extra code to delete linked fields
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true, array $saveTables = null)
    {
        // Normal delete
        $deleted = parent::delete($filter, $saveTables);

        // Look for respondent track key
        if (isset($filter['gr2t_id_respondent_track'])) {
            $trackFilter = $filter['gr2t_id_respondent_track'];
        }  elseif (isset($filter['gto_id_respondent_track'])) {
            $trackFilter = $filter['gto_id_respondent_track'];
        } else {
            $trackFilter = false;
        }

        if ($trackFilter) {
            // Delete track appointments / fields if respondent track was set.
            $this->_deleteTableData(
                    new Zend_DB_Table('gems__respondent2track2appointment'),
                    array('gr2t2a_id_respondent_track' => $trackFilter)
                    );
            $this->_deleteTableData(
                    new Zend_DB_Table('gems__respondent2track2field'),
                    array('gr2t2f_id_respondent_track' => $trackFilter)
                    );
        }

        return $deleted;
    }

    /**
     * Creates new items - in memory only.
     *
     * Contains extra $filter parameter to tell what track for what patient to use to create the new item
     *
     * @param int $count When null a single new item is return, otherwise a nested array with $count new items
     * @param array $filter Filter values telling for what patient and track to create a new item
     * @return array Nested when $count is not null, otherwise just a simple array
     */
    public function loadNew($count = null, array $filter = null)
    {
        $values = null;

        // Create the extra values for the result
        if ($filter) {
            // MUtil_Echo::r($filter);
            $db = $this->getAdapter();

            if (isset($filter['gr2o_patient_nr'], $filter['gr2o_id_organization'])) {
                $sql = "SELECT *,
                            CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')) AS respondent_name
                        FROM gems__respondents INNER JOIN gems__respondent2org ON grs_id_user = gr2o_id_user
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?";
                $values = $db->fetchRow($sql, array($filter['gr2o_patient_nr'], $filter['gr2o_id_organization']));
                $values['gr2t_id_user']         = $values['gr2o_id_user'];
                $values['gr2t_id_organization'] = $values['gr2o_id_organization'];
                $values['gto_id_respondent']    = $values['gr2o_id_user'];
                $values['gto_id_organization']  = $values['gr2o_id_organization'];
            }
            if (isset($filter['gtr_id_track'])) {
                $sql = 'SELECT * FROM gems__tracks INNER JOIN
                            gems__rounds ON gtr_id_track = gro_id_track INNER JOIN
                            gems__surveys ON gro_id_survey = gsu_id_survey INNER JOIN
                            gems__groups ON gsu_id_primary_group = ggp_id_group
                        WHERE gtr_id_track = ?';

                $values = $values + $db->fetchRow($sql, $filter['gtr_id_track']);
                $values['gr2t_id_track'] = $values['gtr_id_track'];
                $values['gr2t_count']    = $values['gtr_survey_rounds'];
                $values['gto_id_round']  = $values['gro_id_round'];
                $values['gto_id_track']  = $values['gtr_id_track'];
                $values['gto_id_survey'] = $values['gro_id_survey'];
            }

            $values['gto_valid_from'] = MUtil_Date::format(new Zend_Date(), $this->get('gto_valid_from', 'storageFormat'));
        }

        // Create standard empty items
        $empties = parent::loadNew($count);

        // Add the empty items to the values
        if ($values) {
            if (null === $count) {
                // Return one array
                return $values + $empties;
            } else {
                // Return array of arrays
                $result = array();
                foreach ($empties as $empty) {
                    $result[] = $values + $empty;
                }
                return $result;
            }

        } else {
            return $empties;
        }
    }

    /**
     * Save a single model item.
     *
     * Contains extra code to copy valuesw synchronized over both tables
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * te decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        // These values are always copied over
        $newValues['gr2t_start_date']      = $newValues['gto_valid_from'];
        $newValues['gr2t_end_date']        = $newValues['gto_valid_until'];
        $newValues['gr2t_end_date_manual'] = $newValues['gto_valid_until_manual'];
        $newValues['gr2t_reception_code']  = isset($newValues['gto_reception_code']) ?
                $newValues['gto_reception_code'] :
                GemsEscort::RECEPTION_OK;

        return parent::save($newValues, $filter, $saveTables);
    }
}