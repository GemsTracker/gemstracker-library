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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentTrackModel.php 345 2011-07-28 08:39:24Z 175780 $
 */

/**
 * The RespondentTrackModel is the model used to display and edit
 * respondent tracks in snippets.
 *
 * The main additions to a standard JoinModel are for filling in the
 * respondent and track info while creating new tracks and key
 * fiddling code for the different use cases.
 *
 * The respondent track model combines all possible information
 * about the respondents track from the tables:
 * - gems__reception_codes
 * - gems__respondent2org
 * - gems__respondent2track
 * - gems__respondents
 * - gems__staff (on created by)
 * - gems__tracks
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Model_RespondentTrackModel extends Gems_Model_HiddenOrganizationModel
{
    public function __construct()
    {
        parent::__construct('surveys', 'gems__respondent2track', 'gr2t');
        $this->addTable('gems__respondents',     array('gr2t_id_user' => 'grs_id_user'));
        $this->addTable('gems__respondent2org',  array('gr2t_id_user' => 'gr2o_id_user'));
        $this->addTable('gems__tracks',          array('gr2t_id_track' => 'gtr_id_track'));
        $this->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'));
        $this->addLeftTable('gems__staff',       array('gr2t_created_by' => 'gsf_id_user'));

        // TODO: altkeys implementatie
        // $this->setKeys($this->_getKeysFor('gems__respondent2track');
        $this->setKeys($this->_getKeysFor('gems__respondent2org') + $this->_getKeysFor('gems__tracks'));

        $this->addColumn(
            "CASE WHEN gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gsf_last_name, ''),
                        ', ',
                        COALESCE(gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                    )
                END",
            'assigned_by');
        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END",
            'row_class');

        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN 1 ELSE 0 END",
            'can_edit');

        $this->addColumn("CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, ''))",
            'respondent_name');
    }

    /**
     * Stores the fields that can be used for sorting or filtering in the
     * sort / filter objects attached to this model.
     *
     * @param array $parameters
     * @return array The $parameters minus the sort & textsearch keys
     */
    public function applyParameters(array $parameters)
    {
        if ($parameters) {
            // Altkey
            if (isset($parameters[Gems_Model::RESPONDENT_TRACK])) {
                $id = $parameters[Gems_Model::RESPONDENT_TRACK];
                unset($parameters[Gems_Model::RESPONDENT_TRACK]);
                $parameters['gr2t_id_respondent_track'] = $id;
            }

            if (isset($parameters[Gems_Model::TRACK_ID])) {
                $id = $parameters[Gems_Model::TRACK_ID];
                unset($parameters[Gems_Model::TRACK_ID]);
                $parameters['gtr_id_track'] = $id;
            }

            return parent::applyParameters($parameters);
        }

        return array();
    }

    /**
     * Creates new items - in memory only. Extended to load information from linked table using $filter().
     *
     * When $filter contains the keys gr2o_patient_nr and gr2o_id_organization the corresponding respondent
     * information is loaded into the new item.
     *
     * When $filter contains the key gtr_id_track the corresponding track information is loaded.
     *
     * The $filter values are also propagated to the corresponding key values in the new item.
     *
     * @param int $count When null a single new item is return, otherwise a nested array with $count new items
     * @param array $filter Allowed key values: gr2o_patient_nr, gr2o_id_organization and gtr_id_track
     * @return array Nested when $count is not null, otherwise just a simple array
     */
    public function loadNew($count = null, array $filter = null)
    {
        $values = array();

        // Create the extra values for the result
        if ($filter) {
            $db = $this->getAdapter();

            if (isset($filter['gr2o_patient_nr'], $filter['gr2o_id_organization'])) {
                $sql = "SELECT *,
                            CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')) AS respondent_name
                        FROM gems__respondents INNER JOIN gems__respondent2org ON grs_id_user = gr2o_id_user
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?";
                $values = $db->fetchRow($sql, array($filter['gr2o_patient_nr'], $filter['gr2o_id_organization']));
                $values['gr2t_id_user']         = $values['gr2o_id_user'];
                $values['gr2t_id_organization'] = $values['gr2o_id_organization'];
            }
            if (isset($filter['gtr_id_track'])) {
                $sql = 'SELECT * FROM gems__tracks WHERE gtr_id_track = ?';
                $values = $values + $db->fetchRow($sql, $filter['gtr_id_track']);
                $values['gr2t_id_track']        = $values['gtr_id_track'];
                $values['gr2t_count']           = $values['gtr_survey_rounds'];
            }
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

    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        $keys = $this->getKeys();

        // This is the only key to save on, no matter
        // the keys used to initiate the model.
        $this->setKeys($this->_getKeysFor('gems__respondent2track'));

        $newValues = parent::save($newValues, $filter, $saveTables);

        $this->setKeys($keys);

        return $newValues;
    }
}
