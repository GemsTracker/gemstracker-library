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
 * Lookup global information from the database, allowing for project specific overrides
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Util_DbLookup extends UtilAbstract
{
    /**
     *
     * @var \Zend_Acl
     */
    protected $acl;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Retrieve a list of orgid/name pairs
     *
     * @return array
     */
    public function getActiveOrganizations()
    {
        $sql = "SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE (gor_active = 1 AND
                            gor_id_organization IN (SELECT gr2o_id_organization FROM gems__respondent2org)) OR
                        gor_id_organization = ?
                    ORDER BY gor_name";

        $orgId = $this->loader->getCurrentUser()->getCurrentOrganizationId();

        return $this->_getSelectPairsCached(__FUNCTION__ . '_' . $orgId, $sql, $orgId, 'organizations', 'natsort');
    }

    /**
     * Return key/value pairs of all active staff members
     *
     * @return array
     */
    public function getActiveStaff()
    {
        $sql = "SELECT gsf_id_user,
                    CONCAT(
                        COALESCE(gsf_last_name, '-'),
                        ', ',
                        COALESCE(gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                        ) AS name
                FROM gems__staff
                WHERE gsf_active = 1
                ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'staff');
    }

    /**
     * Return key/value pairs of all active staff groups
     *
     * @return array
     */
    public function getActiveStaffGroups()
    {
        $sql = "SELECT ggp_id_group, ggp_name
            FROM gems__groups
            WHERE ggp_group_active = 1 AND ggp_staff_members = 1
            ORDER BY ggp_name";

        try {
            $staffGroups = $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'groups');
        } catch (\Exception $exc) {
            // Intentional fallthrough when no db present
            $staffGroups = array();
        }

        return $staffGroups;
    }

    /**
     * Return key/value pairs of all active staff groups
     *
     * @return array
     */
    public function getActiveStaffRoles()
    {
        $sql = "SELECT ggp_id_group, ggp_role
            FROM gems__groups
            WHERE ggp_group_active = 1 AND ggp_staff_members = 1
            ORDER BY ggp_role";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'groups');
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he inherits rights from
     *
     * @return array
     */
    public function getAllowedRespondentGroups()
    {
        $sql = "SELECT ggp_id_group, ggp_name
            FROM gems__groups
            WHERE ggp_group_active = 1 AND ggp_respondent_members = 1
            ORDER BY ggp_name";

        return $this->util->getTranslated()->getEmptyDropdownArray() +
                $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'groups');
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he inherits rights from
     *
     * @return array
     * @deprecated Since 1.7.2 Replaced by loader->getCurrentUser()->getAllowedStaffGroups()
     */
    public function getAllowedStaffGroups()
    {
        return $this->loader->getCurrentUser()->getAllowedStaffGroups();
    }

    /**
     * Return the available Comm templates.
     *
     * @staticvar array $data
     * @return array The tempalteId => subject list
     */
    public function getCommTemplates($mailTarget = false)
    {
        static $data;

        if (! $data) {
            $sql = 'SELECT gct_id_template, gct_name FROM gems__comm_templates ';
            if ($mailTarget) {
                $sql .= 'WHERE gct_target = ? ';
            }
            $sql .= 'ORDER BY gct_name';

            if ($mailTarget) {
                $data = $this->db->fetchPairs($sql, $mailTarget);
            } else {
                $data = $this->db->fetchPairs($sql);
            }
        }

        return $data;
    }

    public function getDefaultGroup()
    {
        $groups  = $this->getActiveStaffGroups();
        $roles   = $this->db->fetchPairs('SELECT ggp_role, ggp_id_group FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
        $current = null;

        foreach (array_reverse($this->acl->getRoles()) as $roleId) {
            if (isset($roles[$roleId], $groups[$roles[$roleId]])) {
                if ($current) {
                    if ($this->acl->inheritsRole($current, $roleId)) {
                        $current = $roleId;
                    }
                } else {
                    $current = $roleId;
                }
            }
        }

        if (isset($roles[$current])) {
            return $roles[$current];
        }
    }

    /**
     * Get the filter to use on the tokenmodel when working with a mailjob.
     *
     * @param array $job
     * @param $respondentId Optional, get for just one respondent
     * @param $organizationId Optional, get for just one organization
     * @return array
     */
    public function getFilterForMailJob($job, $respondentId = null, $organizationId = null)
    {
        // Set up filter
        $filter = array(
        	'can_email'           => 1,
            'gtr_active'          => 1,
            'gsu_active'          => 1,
            'grc_success'         => 1,
        	'gto_completion_time' => NULL,
        	'gto_valid_from <= CURRENT_TIMESTAMP',
            '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)'
        );

        if ($job['gcj_filter_mode'] == 'R') {
            $filter[] = 'gto_mail_sent_date <= DATE_SUB(CURRENT_DATE, INTERVAL ' . $job['gcj_filter_days_between'] . ' DAY)';
            $filter[] = 'gto_mail_sent_num <= ' . $job['gcj_filter_max_reminders'];
        } else {
            $filter['gto_mail_sent_date'] = NULL;
        }
        if ($job['gcj_id_organization']) {
            if ($organizationId && ($organizationId !== $job['gcj_id_organization'])) {
                // Should never return any data
                $filter[] = '1=0';
                return $filter;
            }
            $filter['gto_id_organization'] = $job['gcj_id_organization'];
        }
        if ($job['gcj_id_track']) {
            $filter['gto_id_track'] = $job['gcj_id_track'];
        }
        if ($job['gcj_round_description']) {
            if ($job['gcj_id_track']) {
                $roundIds = $this->db->fetchCol('
                    SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ? AND gro_round_description = ?', array(
                    $job['gcj_id_track'],
                    $job['gcj_round_description'])
                );
            } else {
                $roundIds = $this->db->fetchCol('
                    SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_round_description = ?', array(
                    $job['gcj_round_description'])
                );
            }
            $filter['gto_id_round'] = $roundIds;
        }
        if ($job['gcj_id_survey']) {
            $filter['gto_id_survey'] = $job['gcj_id_survey'];
        }
        if ($respondentId) {
            $filter['gto_id_respondent'] = $respondentId;
        }
        
        if ($job['gcj_target'] == 1) {
            // Only relations
            $filter[] = 'gto_id_relation <> 0';
        } elseif ($job['gcj_target'] == 2) {
            // Only respondents            
            $filter[] = '(gto_id_relation = 0 OR gto_id_relation IS NULL)';
        }        

        return $filter;
    }

    /**
     * The active groups
     *
     * @return array
     */
    public function getGroups()
    {
        $sql = "SELECT ggp_id_group, ggp_name
            FROM gems__groups
            WHERE ggp_group_active = 1
            ORDER BY ggp_name";

        return $this->util->getTranslated()->getEmptyDropdownArray() +
                $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'groups');
    }

    /**
     * Get all active organizations
     *
     * @return array List of the active organizations
     */
    public function getOrganizations()
    {
        $sql = "SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE gor_active = 1
                    ORDER BY gor_name";

        try {
            $organizations = $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'organizations', 'natsort');
        } catch (\Exception $exc) {
            // Intentional fallthrough when no db present
            $organizations = array();
        }

        return $organizations;
    }

    /**
     * Get all organizations that share a given code
     *
     * On empty this will return all organizations
     *
     * @param string $code
     * @return array key = gor_id_organization, value = gor_name
     */
    public function getOrganizationsByCode($code = null)
    {
        static $organizations = array();

        if (is_null($code)) {
            return $this->getOrganizations();
        }

        $sql = "SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE gor_active = 1 and gor_code = ?
                    ORDER BY gor_name";
        return $this->_getSelectPairsCached(__FUNCTION__ . '_' . $code, $sql, $code, 'organizations', 'natsort');
    }

    /**
     * Returns a list of the organizations where users can login.
     *
     * @return array List of the active organizations
     */
    public function getOrganizationsForLogin()
    {
        $sql = "SELECT gor_id_organization, gor_name
            FROM gems__organizations
            WHERE gor_active = 1 AND gor_has_login = 1
            ORDER BY gor_name";

        try {
            $organizations = $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'organizations', 'natsort');
        } catch (\Exception $exc) {
            // Intentional fallthrough when no db present
            $organizations = array();
        }

        return $organizations;
    }

    /**
     * Returns a list of the organizations that have respondents.
     *
     * @return array List of the active organizations
     */
    public function getOrganizationsWithRespondents()
    {
        $sql = "SELECT gor_id_organization, gor_name
                        FROM gems__organizations
                        WHERE gor_active = 1 AND (gor_has_respondents = 1 OR gor_add_respondents = 1)
                        ORDER BY gor_name";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'organizations', 'natsort');
    }

    /**
     * Find the patient nr corresponding to this respondentId / Orgid combo
     *
     * @param int $respondentId
     * @param int $organizationId
     * @return string A patient nr or null
     * @throws \Gems_Exception When the patient does not exist
     */
    public function getPatientNr($respondentId, $organizationId)
    {
        $result = $this->db->fetchOne(
                "SELECT gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ? AND gr2o_id_organization = ?",
                array($respondentId, $organizationId)
                );

        if ($result !== false) {
            return $result;
        }

        throw new \Gems_Exception(
                sprintf($this->_('Respondent id %s not found.'), $respondentId),
                200,
                null,
                sprintf($this->_('In the organization nr %d.'), $organizationId)
                );
    }

    /**
     * Find the respondent id corresponding to this patientNr / Orgid combo
     *
     * @param string $patientId
     * @param int $organizationId
     * @return int A respondent id or null
     * @throws \Gems_Exception When the respondent does not exist
     */
    public function getRespondentId($patientId, $organizationId)
    {
        $result = $this->db->fetchOne(
                "SELECT gr2o_id_user FROM gems__respondent2org WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?",
                array($patientId, $organizationId)
                );

        if ($result !== false) {
            return $result;
        }

        throw new \Gems_Exception(
                sprintf($this->_('Patient number %s not found.'), $patientId),
                200,
                null,
                sprintf($this->_('In the organization nr %d.'), $organizationId)
                );
    }

    /**
     * Find the respondent id name corresponding to this patientNr / Orgid combo
     *
     * @param string $patientId
     * @param int $organizationId
     * @return array ['id', 'name']
     * @throws \Gems_Exception When the respondent does not exist
     */
    public function getRespondentIdAndName($patientId, $organizationId)
    {
        $output = $this->db->fetchRow(
                "SELECT gr2o_id_user as id,
                    TRIM(CONCAT(
                        COALESCE(CONCAT(grs_last_name, ', '), '-, '),
                        COALESCE(CONCAT(grs_first_name, ' '), ''),
                        COALESCE(grs_surname_prefix, ''))) as name
                    FROM gems__respondent2org INNER JOIN
                        gems__respondents ON gr2o_id_user = grs_id_user
                    WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?",
                array($patientId, $organizationId)
                );

        if ($output !== false) {
            return $output;
        }

        throw new \Gems_Exception(
                sprintf($this->_('Patient number %s not found.'), $patientId),
                200,
                null,
                sprintf($this->_('In the organization nr %d.'), $organizationId)
                );
    }

    /**
     * Returns the roles in the acl
     *
     * @return array roleId => ucfirst(roleId)
     */
    public function getRoles()
    {
        $roles = array();

        if ($this->acl) {
            foreach ($this->acl->getRoles() as $role) {
                //Do not translate, only make first one uppercase
                $roles[$role] = ucfirst($role);
            }
        }
        asort($roles);
        
        return $roles;
    }

    /**
     * Returns the roles in the acl with the privilege
     *
     * @return array roleId => ucfirst(roleId)
     */
    public function getRolesByPrivilege($privilege)
    {
        $roles = array();

        if ($this->acl) {
            foreach ($this->acl->getRoles() as $role) {
                if ($this->acl->isAllowed($role, null, $privilege)) {
                    //Do not translate, only make first one uppercase
                    $roles[$role] = ucfirst($role);
                }
            }
        }

        return $roles;
    }

    /**
     * Get all round descriptions for exported
     *
     * @param int $trackId Optional track id
     * @param int $surveyId Optional survey id
     * @return array
     */
    public function getRoundsForExport($trackId = null, $surveyId = null)
    {
        // Read some data from tables, initialize defaults...
        $select = $this->db->select();

        // Fetch all round descriptions
        $select->from('gems__tokens', array('gto_round_description', 'gto_round_description'))
            ->distinct()
            ->where('gto_round_description IS NOT NULL AND gto_round_description != ""')
            ->order(array('gto_round_description'));

        if (!empty($trackId)) {
            $select->where('gto_id_track = ?', (int) $trackId);
        }

        if (!empty($surveyId)) {
            $select->where('gto_id_survey = ?', (int) $surveyId);
        }

        $result = $this->db->fetchPairs($select);

        return $result;
    }

    /**
     * Returns the roles in the acl
     *
     * @return array roleId => ucfirst(roleId)
     */
    public function getSources()
    {
        $sql = "SELECT gso_id_source, gso_source_name
                    FROM gems__sources
                    ORDER BY gso_source_name";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'sources');
    }

    /**
     * Return key/value pairs of all staff members, currently active or not
     *
     * @return array
     */
    public function getStaff()
    {
        $sql = "SELECT gsf_id_user,
                        CONCAT(
                            COALESCE(gsf_last_name, '-'),
                            ', ',
                            COALESCE(gsf_first_name, ''),
                            COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                            )
                    FROM gems__staff
                    ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'staff') +
                array(
                    \Gems_User_UserLoader::SYSTEM_USER_ID => \MUtil_Html::raw($this->_('&laquo;system&raquo;')),
                );
    }

    /**
     * Return key/value pairs of all staff groups, including not active
     *
     * @return array
     */
    public function getStaffGroups()
    {
        $sql = "SELECT ggp_id_group, ggp_name
            FROM gems__groups
            WHERE ggp_staff_members = 1
            ORDER BY ggp_name";

        return $this->_getSelectPairsCached(__FUNCTION__, $sql, null, 'groups');
    }

    /**
     * Get all surveys that can be exported
     *
     * For export not only active surveys should be returned, but all surveys that can be exported.
     * As this depends on the kind of source used it is in this method so projects can change to
     * adapt to their own sources.
     *
     * @param int $trackId Optional track id
     * @return array
     */
    public function getSurveysForExport($trackId = null, $roundDescription = null, $flat = false)
    {
        // Read some data from tables, initialize defaults...
        $select = $this->db->select();

        // Fetch all surveys
        $select->from('gems__surveys')
            ->join('gems__sources', 'gsu_id_source = gso_id_source')
            ->where('gso_active = 1')
            //->where('gsu_surveyor_active = 1')
            // Leave inactive surveys, we toss out the inactive ones for limesurvey
            // as it is no problem for OpenRosa to have them in
            ->order(array('gsu_active DESC', 'gsu_survey_name'));

        if ($trackId) {
            if ($roundDescription) {
                $select->where('gsu_id_survey IN (SELECT gto_id_survey FROM gems__tokens WHERE gto_id_track = ? AND gto_round_description = ' . $this->db->quote($roundDescription) . ')', $trackId);
            } else {
                $select->where('gsu_id_survey IN (SELECT gto_id_survey FROM gems__tokens WHERE gto_id_track = ?)', $trackId);
            }
        } elseif ($roundDescription) {
            $select->where('gsu_id_survey IN (SELECT gto_id_survey FROM gems__tokens WHERE gto_round_description = ?)', $roundDescription);
        }

        $result = $this->db->fetchAll($select);

        if ($result) {
            // And transform to have inactive surveys in gems and source in a
            // different group at the bottom
            $surveys = array();
            $inactive = $this->_('inactive');
            $sourceInactive = $this->_('source inactive');
            foreach ($result as $survey) {
                $id   = $survey['gsu_id_survey'];
                $name = $survey['gsu_survey_name'];
                if ($survey['gsu_surveyor_active'] == 0) {
                    // Inactive in the source, for LimeSurvey this is a problem!
                    if (strpos($survey['gso_ls_class'], 'LimeSurvey') === false) {
                        if ($flat) {
                            $surveys[$id] = $name . " ($sourceInactive) ";
                        } else {
                            $surveys[$sourceInactive][$id] = $name;
                        }
                    }
                } elseif ($survey['gsu_active'] == 0) {
                    if ($flat) {
                        $surveys[$id] = $name . " ($inactive) ";
                    } else {
                        $surveys[$inactive][$id] = $name;
                    }
                } else {
                    $surveys[$id] = $name;
                }
            }
        } else {
            $surveys = array();
        }

        return $surveys;
    }

    /**
     *
     * @return array
     */
    public function getUserConsents()
    {
        $sql = "SELECT gco_description, gco_description FROM gems__consents ORDER BY gco_order";

        return $this->_getSelectPairsProcessedCached(__FUNCTION__, $sql, array($this, '_'), null, 'consents');
    }
}
