<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use Gems\Util\UtilAbstract;

/**
 * Class for general track utility functions
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Util_TrackData extends UtilAbstract
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
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * that are active
     *
     * @param mixed $orgs Either an array of org ids or an organization id or an sql select where statement
     * @return array
     */
    public function getActiveTracks($orgs = '1=1')
    {
        if (is_array($orgs) || is_int($orgs)) {
            $cacheId  = __CLASS__ . '_' . __FUNCTION__ . '_o' .  parent::cleanupForCacheId(implode('_', (array) $orgs));
            $orgWhere = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", (array) $orgs) .
                "|') > 0)";
        } else {
            $orgWhere = $orgs ? $orgs : '1=1';
            $cacheId  = __CLASS__ . '_' . __FUNCTION__ . '_' . parent::cleanupForCacheId($orgWhere);
        }

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gtr_id_track, gtr_track_name
                    FROM gems__tracks
                    WHERE gtr_active=1 AND $orgWhere
                    ORDER BY gtr_track_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('tracks'));
        return $results;
    }

    /**
     * Returns array (id => name) of all ronds in all tracks, sorted by order
     *
     * @return array
     */
    public function getAllRounds()
    {
        $sql = "SELECT gro_id_round,
                        CONCAT(gro_id_order, ' - ', SUBSTR(gsu_survey_name, 1, 80)) AS name
                    FROM gems__rounds INNER JOIN gems__surveys ON gro_id_survey = gsu_id_survey
                    ORDER BY gro_id_order";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, array(), 'tracks');
    }

    /**
     * Returns array (description => description) of all round descriptions in all tracks, sorted by name
     *
     * @return array
     */
    public function getAllRoundDescriptions()
    {
        $sql = "SELECT gro_round_description, gro_round_description
            FROM gems__rounds
            WHERE gro_round_description IS NOT NULL AND gro_round_description != '' AND gro_id_round != 0
            GROUP BY gro_round_description";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, array(), 'tracks');
    }

    /**
     * Retrieve an array of key/value pairs for gsu_id_survey and gsu_survey_name
     * @param  boolean $active Only show active surveys Default: False
     * @return array of survey Id and survey name pairs
     */
    public function getAllSurveys($active=false)
    {
        if ($active) {
            $sql = "SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys WHERE gsu_active = 1 ORDER BY gsu_survey_name";
        } else {
            $sql = "SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys ORDER BY gsu_survey_name";
        }

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, array(), 'surveys');
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
            		SUBSTR(CONCAT_WS(
            			" - ", gsu_survey_name, CASE WHEN LENGTH(TRIM(gsu_survey_description)) = 0 THEN NULL ELSE gsu_survey_description END
            		), 1, 50),
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

        $select = "SELECT gtr_id_track, gtr_track_name
                    FROM gems__tracks
                    WHERE gtr_track_class != 'SingleSurveyEngine'
                    ORDER BY gtr_track_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('tracks'));
        return $results;
    }

    /**
     * Get an array of translated labels for the date units used by this engine
     *
     * @return array date_unit => label
     * @deprecated since 1.7.1 use Translated->getDatePeriodUnits()
     */
    public function getDateUnitsList()
    {
        return array(
            'S' => $this->translate->_('Seconds'),
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
     * @param int $organizationId Optional organization id
     * @return array
     */
    public function getInsertableSurveys($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        if (null === $organizationId) {
            $orgWhere = '';
        } else {
            $orgId    = intval($organizationId);
            $orgWhere = "AND gsu_insert_organizations LIKE '%|$orgId|%'";
        }
        $select = "SELECT gsu_id_survey, gsu_survey_name
            FROM gems__surveys
            WHERE gsu_active = 1 AND gsu_insertable = 1 $orgWhere
            ORDER BY gsu_survey_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('surveys'));
        return $results;
    }

    /**
     *
     * @param int $respId
     * @param int $orgId
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
     * @deprecated Since 1.7.1 getAllTracks() is all we need
     */
    public function getSteppedTracks()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gtr_id_track, gtr_track_name
                    FROM gems__tracks
                    WHERE gtr_track_class != 'SingleSurveyEngine'
                    ORDER BY gtr_track_name";

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('tracks'));
        return $results;
    }

    /**
     * Get the Rounds that use this survey
     *
     * @param int $surveyId
     * @return array
     */
    public function getSurveyRounds($surveyId)
    {
        $sql = "SELECT gro.gro_id_round,
                CONCAT(gtr.gtr_track_name, ' (', gro.gro_id_order, ') - ', gro.gro_round_description)
            FROM gems__rounds AS gro, gems__tracks AS gtr
            WHERE gro.gro_id_track = gtr.gtr_id_track AND gro.gro_id_survey = ?
            ORDER BY gtr.gtr_track_name, gro.gro_id_order";

        return $this->_getSelectPairsCached(__FUNCTION__. '_' . $surveyId, $sql, $surveyId, 'surveys');
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
     * Get all the surveys for a certain organization id
     *
     * @param int $organizationId
     * @return array survey id => survey name
     */
    public function getSurveysFor($organizationId)
    {
        if ($organizationId !== null) {
            $where = "AND EXISTS (SELECT 1 FROM gems__rounds
                INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                WHERE gro_id_survey = gsu_id_survey AND
                gtr_organizations LIKE '%|" . (int) $organizationId . "|%')";
        } else {
            $where = "";
        }

        $sql = "SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys WHERE gsu_active = 1 " .
            $where . " ORDER BY gsu_survey_name ASC";

        return $this->_getSelectPairsCached(__FUNCTION__. '_' . $organizationId, $sql, array(), 'surveys');
    }

    /**
     * Get surveys that do not have export codes
     *
     * @param int $surveyId
     * @return array
     */
    public function getSurveysWithoutExportCode()
    {
        $sql = "SELECT gsu_id_survey, gsu_survey_name
            FROM gems__surveys
            WHERE gsu_export_code IS NULL OR gsu_export_code = ''
            ORDER BY gsu_survey_name";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, array(), 'surveys');
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
     * Returns array (id => name) of all tracks accessible by this organisation, sorted alphabetically
     *
     * @param array $orgs orgId => org name
     * @return array
     */
    public function getTracksForOrgs(array $orgs)
    {
        $orgWhere = "(INSTR(gtr_organizations, '|" .
                implode("|') > 0 OR INSTR(gtr_organizations, '|", array_keys($orgs)) .
                "|') > 0)";

        $select = "SELECT gtr_id_track, gtr_track_name
                    FROM gems__tracks
                    WHERE gtr_track_class != 'SingleSurveyEngine' AND
                        $orgWhere
                    ORDER BY gtr_track_name";

        return $this->db->fetchPairs($select);
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
