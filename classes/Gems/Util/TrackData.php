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
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class for general track utility functions
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Util_TrackData extends \Gems_Registry_TargetAbstract
{
    /**
     * When displaying tokens for a respondent only those of
     * the current organization should be shown.
     */
    const SEE_CURRENT_ONLY = 1;

    /**
     * When displaying tokens for a respondent all tokens from all organizations
     * should be shown.
     */
    const SEE_EVERYTHING = 2;

    /**
     * When displaying tokens for a respondent all tokens from organizations
     * accessible by the user should be shown.
     */
    const SEE_ALL_ACCESSIBLE = 3;

    /**
     * Determine what to show when displaying tokens for a respondent.
     * The default is only those of the current organization.
     *
     * @var int One of the self::SEE_ constants
     */
    public $accessMode = self::SEE_CURRENT_ONLY;

    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;


    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * that are active
     *
     * @return array
     */
    public function getActiveSurveys()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gsu_id_survey, gsu_survey_name
            FROM gems__surveys
            WHERE gsu_active = 1
            ORDER BY gsu_survey_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys'));
        return $results;
    }

    /**
     * Returns array (id => name) of all ronds in all tracks, sorted by order
     *
     * @return array
     */
    public function getAllRounds()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gro_id_round,
                        CONCAT(gro_id_order, ' - ', SUBSTR(gsu_survey_name, 1, 80)) AS name
                    FROM gems__rounds INNER JOIN gems__surveys ON gro_id_survey = gsu_id_survey
                    ORDER BY gro_id_order";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('rounds', 'surveys'));
        return $results;
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     *
     * @return array
     */
    public function getAllSurveys()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys ORDER BY gsu_survey_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys'));
        return $results;
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name plus gsu_survey_description
     *
     * @return array
     */
    public function getAllSurveysAndDescriptions()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = 'SELECT gsu_id_survey,
            	CONCAT(
            		LEFT(CONCAT_WS(
            			" - ", gsu_survey_name, CASE WHEN LENGTH(TRIM(gsu_survey_description)) = 0 THEN NULL ELSE gsu_survey_description END
            		), 50),
        			CASE WHEN gsu_active = 1 THEN " (' . $this->translate->_('Active') . ')" ELSE " (' . $this->translate->_('Inactive') . ')" END
    			)
            	FROM gems__surveys ORDER BY gsu_survey_name';

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys'));
        return $results;
    }

    /**
     * Returns array (id => name) of all tracks, sorted alphabetically
     * @return array
     */
    public function getAllTracks()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gtr_id_track, gtr_track_name FROM gems__tracks ORDER BY gtr_track_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('tracks'));
        return $results;
    }

    /**
     * Get an array of translated labels for the date units used by this engine
     *
     * @param boolean $validAfter True if it concenrs _valid_after_ dates
     * @return array date_unit => label
     */
    public function getDateUnitsList($validAfter)
    {
        return array(
            'N' => $this->translate->_('Minutes'),
            'H' => $this->translate->_('Hours'),
            'D' => $this->translate->_('Days'),
            'W' => $this->translate->_('Weeks'),
            'M' => $this->translate->_('Months'),
            'Q' => $this->translate->_('Quarters'),
            'Y' => $this->translate->_('Years')
        );
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * that are active and are insertable
     *
     * @return array
     */
    public function getInsertableSurveys()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gsu_id_survey, gsu_survey_name
            FROM gems__surveys
            WHERE gsu_active = 1 AND gsu_insertable = 1
            ORDER BY gsu_survey_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys'));
        return $results;
    }

    /**
     *
     * @param type $respId
     * @param type $orgId
     */
    public function getRespondentTokenFilter($respId, $orgId = null)
    {
        if (null === $orgId) {
            $orgId = $this->loader->getCurrentUser()->getCurrentOrganizationId();
        }
    } // */

    /**
     * Returns array (id => name) of all ronds in a track, sorted by order
     *
     * @param int $trackId
     * @return array
     */
    public function getRoundsFor($trackId)
    {
        return $this->db->fetchPairs("SELECT gro_id_round, CONCAT(gro_id_order, ' - ', SUBSTR(gsu_survey_name, 1, 80)) AS name FROM gems__rounds INNER JOIN gems__surveys ON gro_id_survey = gsu_id_survey WHERE gro_id_track = ? ORDER BY gro_id_order", $trackId);
    }

    /**
     * Returns array (id => name) of all 'T' tracks, sorted alphabetically
     * @return array
     */
    public function getSteppedTracks()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gtr_id_track, gtr_track_name FROM gems__tracks ORDER BY gtr_track_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('tracks'));
        return $results;
    }

    /**
     * Get all the surveys for a certain code
     *
     * @param string $code
     * @return array survey id => survey name
     */
    public function getSurveysByCode($code)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $code;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__surveys', array('gsu_id_survey', 'gsu_survey_name'))
                ->where("gsu_code = ?", $code)
                ->where("gsu_active = 1")
                ->order('gsu_survey_name');

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys'));
        return $results;
    }

    /**
     * Returns array (id => name) of the track date fields for this track, sorted by order
     *
     * @param int $trackId
     * @return array
     */
    public function getTrackDateFields($trackId)
    {
        $dateFields = $this->db->fetchPairs("SELECT gtf_id_field, gtf_field_name FROM gems__track_fields WHERE gtf_id_track = ? AND gtf_field_type = 'date' ORDER BY gtf_id_order", $trackId);

        if (! $dateFields) {
            $dateFields = array();
        }

        return $dateFields;
    }

    /**
     * Get all the tracks for a certain survey
     *
     * @param int $surveyId
     * @return array survey id => survey name
     */
    public function getTracksBySurvey($surveyId)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $surveyId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__tracks', array('gtr_id_track', 'gtr_track_name'))
                ->joinInner('gems__rounds', 'gtr_id_track = gro_id_track', array())
                ->where("gro_id_survey = ?", $surveyId)
                ->where("gtr_active = 1")
                ->where("gro_active = 1")
                ->order('gtr_track_name');

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys', 'tracks'));
        return $results;
    }

    /**
     * Returns array (id => name) of all track date fields, sorted alphabetically
     *
     * @return array
     */
    public function getTracksDateFields()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gtf_id_field, gtf_field_name
                    FROM gems__track_fields
                    WHERE gtf_field_type = 'date'
                    ORDER BY gtf_field_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('tracks'));
        return $results;
    }

    /**
     * Returns title of the track.
     *
     * @param int $trackId
     * @return string
     */
    public function getTrackTitle($trackId)
    {
        $tracks = $this->getAllTracks();

        if ($tracks && isset($tracks[$trackId])) {
            return $tracks[$trackId];
        }
    }
}
