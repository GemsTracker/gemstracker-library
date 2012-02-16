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
 * The StandardTokenModel is the model used to display tokens
 * in e.g. browse tables. It can also be used to edit standard
 * tokens, though track engines may supply different models for
 * editing, as the SingleSurveyTokeModel does.
 *
 * The standard token model combines all possible information
 * about the token from the tables:
 * - gems__groups
 * - gems__organizations
 * - gems__reception_codes
 * - gems__respondent2org
 * - gems__respondent2track
 * - gems__respondents
 * - gems__staff (on created by)
 * - gems__surveys
 * - gems__tracks
 *
 * The MUtil_Registry_TargetInterface is implemented so that
 * these models can take care of their own formatting.
 *
 * @see Gems_Tracker_Engine_TrackEngineInterface
 * @see Gems_Tracker_Model_SingleSurveyTokenModel
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Model_StandardTokenModel extends Gems_Model_HiddenOrganizationModel implements MUtil_Registry_TargetInterface
{
    /**
     *
     * @var boolean Set to true when data in the respondent2track table must be saved as well
     */
    protected $saveRespondentTracks = false;

    /**
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * @var Gems_Util
     */
    protected $util;

    /**
     * Create the model with standard tables and calculated columns
     */
    public function __construct()
    {
        parent::__construct('token', 'gems__tokens', 'gto');

        if ($this->saveRespondentTracks) {
            // Set the correct prefix
            $this->saveRespondentTracks = 'gr2t';
        }

        $this->addTable(    'gems__tracks',           array('gto_id_track' => 'gtr_id_track'));
        $this->addTable(    'gems__rounds',           array('gto_id_round' => 'gro_id_round'));
        $this->addTable(    'gems__surveys',          array('gto_id_survey' => 'gsu_id_survey'));
        $this->addTable(    'gems__groups',           array('gsu_id_primary_group' => 'ggp_id_group'));
        $this->addTable(    'gems__respondents',      array('gto_id_respondent' => 'grs_id_user'));
        $this->addTable(    'gems__respondent2org',   array('gto_id_organization' => 'gr2o_id_organization', 'gto_id_respondent' => 'gr2o_id_user'));
        $this->addTable(    'gems__respondent2track', array('gr2t_id_respondent_track' => 'gto_id_respondent_track'), $this->saveRespondentTracks);
        $this->addTable(    'gems__organizations',    array('gto_id_organization' => 'gor_id_organization'));
        $this->addTable(    'gems__reception_codes',  array('gto_reception_code' => 'grc_id_reception_code'));
        $this->addLeftTable('gems__staff',            array('gr2t_created_by' => 'gems__staff.gsf_id_user'));

        $this->addColumn(
            "CASE WHEN gtr_track_type = 'T' THEN gtr_track_name ELSE NULL END",
            'calc_track_name',
            'gtr_track_name');
        $this->addColumn(
            "CASE WHEN gtr_track_type = 'T' THEN gr2t_track_info ELSE NULL END",
            'calc_track_info',
            'gr2t_track_info');
        $this->addColumn(
            "CASE WHEN gtr_track_type = 'T' THEN gto_round_description ELSE gr2t_track_info END",
            'calc_round_description',
            'gto_round_description');

        $this->addColumn(
            "CASE WHEN CHAR_LENGTH(gsu_survey_name) > 30 THEN CONCAT(SUBSTRING(gsu_survey_name, 1, 28), '...') ELSE gsu_survey_name END",
            'survey_short',
            'gsu_survey_name');
        $this->addColumn(
            "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
            'gsu_has_pdf');

        $this->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN 0 ELSE 1 END',
            'is_completed');
        $this->addColumn(
            'CASE WHEN grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END',
            'can_be_taken');
        $this->addColumn(
            "CASE WHEN grc_success = 1 THEN '' ELSE 'deleted' END",
            'row_class');
        $this->addColumn(
            "CASE WHEN grc_success = 1 AND grs_email IS NOT NULL AND grs_email != '' AND ggp_respondent_members = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN 1 ELSE 0 END",
            'can_email');

        $this->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');
        $this->addColumn(
            "CASE WHEN gems__staff.gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    )
                END",
            'assigned_by');
        $this->addColumn(new Zend_Db_Expr("'token'"), Gems_Model::ID_TYPE);
        /*    TRIM(CONCAT(
                CASE WHEN gto_created = gto_changed OR DATEDIFF(CURRENT_TIMESTAMP, gto_changed) > 0 THEN '' ELSE 'changed' END,
                ' ',
                CASE WHEN DATEDIFF(CURRENT_TIMESTAMP, gto_created) > 0 THEN '' ELSE 'created' END
            ))"), 'row_class'); // */

        if ($this->saveRespondentTracks) {
            // The save order is reversed in this case.
            $this->_saveTables = array_reverse($this->_saveTables);
        }

        //If we are allowed to see who filled out a survey, modify the model accordingly
        $escort = GemsEscort::getInstance();
        if ($escort->hasPrivilege('pr.respondent.who')) {
            $this->addLeftTable('gems__staff', array('gto_by' => 'gems__staff_2.gsf_id_user'));
            $this->addColumn('CASE WHEN gems__staff_2.gsf_id_user IS NULL THEN ggp_name ELSE COALESCE(CONCAT_WS(" ", CONCAT(COALESCE(gems__staff_2.gsf_last_name,"-"),","), gems__staff_2.gsf_first_name, gems__staff_2.gsf_surname_prefix)) END', 'ggp_name');
        }
        if ($escort->hasPrivilege('pr.respondent.result')) {
            $this->addColumn('gto_result', 'calc_result', 'gto_result');
        } else {
            $this->addColumn(new Zend_Db_Expr('NULL'), 'calc_result', 'gto_result');
        }

        $this->useTokenAsKey();
    }

    /**
     * Allows the source to set request.
     *
     * @param string $name Name of resource to set
     * @param mixed $resource The resource.
     * @return boolean True if $resource was OK
     */
    public function answerRegistryRequest($name, $resource)
    {
        if (MUtil_Registry_Source::$verbose) {
            MUtil_Echo::r('Resource set: ' . get_class($this) . '->' . __FUNCTION__ .
                    '("' . $name . '", ' .
                    (is_object($resource) ? get_class($resource) : gettype($resource)) . ')');
        }
        $this->$name = $resource;

        return true;
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @return Gems_Tracker_Model_StandardTokenModel
     */
    public function applyFormatting()
    {
        $translated = $this->util->getTranslated();

        // Token items
        $this->set('gto_id_token',          'label', $this->translate->_('Token'));
        $this->set('gto_round_description', 'label', $this->translate->_('Round'));
        $this->set('gto_valid_from',        'label', $this->translate->_('Measure(d) on'),  'formatFunction', $translated->formatDateNever,   'tdClass', 'date');
        $this->set('gto_valid_until',       'label', $this->translate->_('Valid until'),    'formatFunction', $translated->formatDateForever, 'tdClass', 'date');
        $this->set('gto_mail_sent_date',    'label', $this->translate->_('Last contact'),   'formatFunction', $translated->formatDateNever,   'tdClass', 'date');
        $this->set('gto_completion_time',   'label', $this->translate->_('Completed'),      'formatFunction', $translated->formatDateNa,      'tdClass', 'date');
        $this->set('gto_duration_in_sec',   'label', $this->translate->_('Duration in seconds'));
        $this->set('gto_result',            'label', $this->translate->_('Score'));
        $this->set('gto_comment',           'label', $this->translate->_('Comments'));
        $this->set('gto_changed',           'label', $this->translate->_('Changed on'));

        // Calculatd items
        $this->set('assigned_by',           'label', $this->translate->_('Assigned by'));
        $this->set('respondent_name',       'label', $this->translate->_('Respondent name'));

        // Other items
        $this->set('ggp_name',              'label', $this->translate->_('Assigned to'));
        $this->set('grc_description',       'label', $this->translate->_('Rejection code'), 'formatFunction', array($this->translate, '_'));
        $this->set('gr2o_patient_nr',       'label', $this->translate->_('Respondent nr'));
        $this->set('gr2t_track_info',       'label', $this->translate->_('Description'));
        $this->set('gsu_survey_name',       'label', $this->translate->_('Survey'));
        $this->set('gtr_track_name',        'label', $this->translate->_('Track'));

        return $this;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->translate && $this->util;
    }

    /**
     * Filters the names that should not be requested.
     *
     * Can be overriden.
     *
     * @param string $name
     * @return boolean
     */
    protected function filterRequestNames($name)
    {
        return '_' !== $name[0];
    }

    /**
     * Allows the loader to know the resources to set.
     *
     * @return array of string names
     */
    public function getRegistryRequests()
    {
        return array_filter(array_keys(get_object_vars($this)), array($this, 'filterRequestNames'));
    }

    public function useRespondentTrackAsKey()
    {
        $this->setKeys($this->_getKeysFor('gems__respondent2org') + $this->_getKeysFor('gems__tracks'));

        return $this;
    }

    public function useTokenAsKey()
    {
        $this->setKeys($this->_getKeysFor('gems__tokens'));

        return $this;
    }
}