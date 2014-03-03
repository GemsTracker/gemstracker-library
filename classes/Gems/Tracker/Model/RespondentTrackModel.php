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
 * @version    $Id$
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
    /**
     * Optional, required for details or $trackEngine, $patientId and $organizationId must be set
     *
     * @var Gems_Tracker_RespondentTrack
     */
    protected $respondentTrack;

    /**
     * Optional, required for details when $respondentTrack is not set
     *
     * @var int Organization Id
     */
    protected $organizationId;

    /**
     * Optional, required for details when $respondentTrack is not set
     *
     * @var int Patient Id
     */
    protected $patientId;

    /**
     * Set by application
     *
     * @var int respondent id
     */
    protected $respondentId = null;

    /**
     * Optional, required for details when $respondentTrack is not set
     *
     * @var Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Default constructor
     *
     * @param string $name Optional different name for model
     */
    public function __construct($name = 'surveys')
    {
        parent::__construct($name, 'gems__respondent2track', 'gr2t', true);
        $this->addTable('gems__respondents',     array('gr2t_id_user' => 'grs_id_user'), 'grs', false);
        $this->addTable(
                'gems__respondent2org',
                array('gr2t_id_user' => 'gr2o_id_user', 'gr2t_id_organization' => 'gr2o_id_organization'),
                'gr2o',
                false
                );
        $this->addTable('gems__tracks',          array('gr2t_id_track' => 'gtr_id_track'), 'gtr', false);
        $this->addTable('gems__reception_codes', array('gr2t_reception_code' => 'grc_id_reception_code'), 'grc', false);
        $this->addLeftTable('gems__staff',       array('gr2t_created_by' => 'gsf_id_user'));

        // No need to send all this information to the user
        $this->setCol($this->getItemsFor('table', 'gems__staff'), 'elementClass', 'None');

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
     * Add tracking off manual end date changes by the user
     *
     * @param mixed $value The value to store when the tracked field has changed
     * @return \Gems_Tracker_Model_StandardTokenModel
     */
    public function addEditTracking()
    {
        $changer = new MUtil_Model_Type_ChangeTracker($this, 1, 0);

        $changer->apply('gr2t_end_date_manual', 'gr2t_end_date');

        return $this;
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Model_RespondentModel
     */
    public function applyBrowseSettings()
    {
        $formatDate = $this->util->getTranslated()->formatDate;

        $this->resetOrder();
        $this->set('gtr_track_name',    'label', $this->_('Track'));
        $this->set('gr2t_track_info',   'label', $this->_('Description'),
            'description', $this->_('Enter the particulars concerning the assignment to this respondent.'));
        $this->set('assigned_by',       'label', $this->_('Assigned by'));
        $this->set('gr2t_start_date',   'label', $this->_('Start'),
        	'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $formatDate,
            'default', new Zend_Date());
        $this->set('gr2t_end_date',   'label', $this->_('Ending on'),
        	'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $formatDate);
        $this->set('gr2t_reception_code');
        $this->set('gr2t_comment',       'label', $this->_('Comment'));
    }

    /**
     * Set those settings needed for the detailed display
     *
     * Either respondentTrack or trackEngine and patientId and organizationId must have been set.
     *
     * @param boolean $edit When true edit fields are added
     * @return \Gems_Model_RespondentModel
     */
    public function applyDetailSettings($edit = false)
    {
        $this->resetOrder();

        $formatDate = $this->util->getTranslated()->formatDate;

        if ($this->respondentTrack) {
            $this->organizationId = $this->respondentTrack->getOrganizationId();
            $this->respondentId   = $this->respondentTrack->getRespondentId();
            $this->trackEngine    = $this->respondentTrack->getTrackEngine();
        }

        $this->set('gr2o_patient_nr',   'label', $this->_('Respondent number'));
        $this->set('respondent_name',   'label', $this->_('Respondent name'));
        $this->set('gtr_track_name',    'label', $this->_('Track'));

        $this->set('gr2t_mailable',
                'label', $this->_('May be mailed'),
                'elementClass', 'radio',
                'separator', ' ',
                'multiOptions', array(
                        '1' => $this->_('Yes'),
                        '0' => $this->_('No'),
                    )
                );

        // Integrate fields
        if ($this->trackEngine instanceof Gems_Tracker_Engine_TrackEngineInterface) {
            $this->trackEngine->addFieldsToModel(
                    $this,
                    $this->respondentId,
                    $this->organizationId,
                    $this->patientId,
                    $edit
                    );
        }
        
        $this->set('gr2t_track_info',   'label', $this->_('Description'));
        $this->set('assigned_by',       'label', $this->_('Assigned by'));
        $this->set('gr2t_start_date',   'label', $this->_('Start'),
            'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $formatDate);
        $this->set('gr2t_end_date',     'label', $this->_('Ending on'),
            'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $formatDate);
        $this->set('gr2t_comment',      'label', $this->_('Comment'));
    }

    /**
     * Set those values needed for editing
     *
     * Either respondentTrack or trackEngine and patientId and organizationId must have been set.
     *
     * @param mixed $locale The locale for the settings
     * @return \Gems_Model_RespondentModel
     */
    public function applyEditSettings()
    {
        $this->applyDetailSettings(true);
        $this->addEditTracking();

        $this->set('gr2o_patient_nr', 'elementClass', 'Exhibitor');
        $this->set('respondent_name', 'elementClass', 'Exhibitor');
        $this->set('gtr_track_name',  'elementClass', 'Exhibitor');

        // Fields set in details

        $this->set('gr2t_track_info', 'elementClass', 'None');
        $this->set('assigned_by',     'elementClass', 'None');
        $this->set('gr2t_start_date', 'elementClass', 'Date',
                'default', new Zend_Date(),
                'size',    30
                );
        $this->set('gr2t_end_date',   'elementClass', 'Date',
                'default', null,
                'size',    30
                );
        $this->set('gr2t_comment',    'elementClass', 'None');
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

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null)
    {
        $keys = $this->getKeys();

        // This is the only key to save on, no matter
        // the keys used to initiate the model.
        $this->setKeys($this->_getKeysFor('gems__respondent2track'));

        // Change the end date until the end of the day
        if (isset($newValues['gr2t_end_date']) && $newValues['gr2t_end_date'])  {
            $displayFormat = $this->get('gr2t_end_date', 'dateFormat');
            if ( ! $displayFormat) {
                $displayFormat = MUtil_Model_FormBridge::getFixedOption('date', 'dateFormat');
            }

            // Of course do not do so when we got a time format
            if (! MUtil_Date_Format::getTimeFormat($displayFormat)) {
                $newValues['gr2t_end_date'] = new MUtil_Date($newValues['gr2t_end_date'], $displayFormat);
                $newValues['gr2t_end_date']->setTimeToDayEnd();
            }
        }

        $newValues = parent::save($newValues, $filter);

        $this->setKeys($keys);

        return $newValues;
    }

    /**
     * Set when available, required for details when $respondentTrack is not set
     *
     * @param string $patientId
     * @param int $organizationId
     * @return \Gems_Tracker_Model_RespondentTrackModel (continuation pattern)
     */
    public function setPatientAndOrganization($patientId, $organizationId)
    {
        $this->patientId      = $patientId;
        $this->organizationId = $organizationId;

        return $this;
    }

    /**
     * Set when available, required for details or $trackEngine, $patientId and
     * $organizationId must be set
     *
     * @param Gems_Tracker_RespondentTrack $respondentTrack
     * @return \Gems_Tracker_Model_RespondentTrackModel (continuation pattern)
     */
    public function setRespondentTrack(Gems_Tracker_RespondentTrack $respondentTrack)
    {
        $this->respondentTrack = $respondentTrack;

        return $this;
    }

    /**
     * Set when available, required for details when $respondentTrack is not set
     *
     * @param Gems_Tracker_Engine_TrackEngineInterface $trackEngine
     * @return \Gems_Tracker_Model_RespondentTrackModel (continuation pattern)
     */
    public function setTrackEngine(Gems_Tracker_Engine_TrackEngineInterface $trackEngine)
    {
        $this->trackEngine = $trackEngine;

        return $this;
    }
}
