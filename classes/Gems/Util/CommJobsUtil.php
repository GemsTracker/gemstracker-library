<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Util;

/**
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 13-Aug-2019 14:41:34
 */
class CommJobsUtil extends UtilAbstract
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @param array $filter
     * @param string $mode
     * @param int $daysBetween
     * @param int $maxReminders
     * @param boolean $forceSent Ignore previous sent mails
     */
    protected function _addModeFilter(array &$filter, $mode, $daysBetween, $maxReminders, $forceSent = false)
    {
        switch ($mode) {
            case 'B':   // First mail before expiry
                if (! $forceSent) {
                    $filter['gto_mail_sent_date'] = null;
                }
                $filter[] = "CURRENT_DATE() = DATE(DATE_SUB(gto_valid_until, INTERVAL $daysBetween DAY))";
                break;

            case 'E':   // Reminder before expiry
                $filter[] = "gto_mail_sent_date < CURRENT_DATE()";
                $filter[] = "CURRENT_DATE() = DATE(DATE_SUB(gto_valid_until, INTERVAL $daysBetween DAY))";
                break;

            case 'R':   // Reminder after first email
                $filter[] = "gto_mail_sent_date <= DATE_SUB(CURRENT_DATE, INTERVAL $daysBetween DAY)";
                $filter[] = "gto_mail_sent_num <= $maxReminders";
                break;

            case 'N':   // First email
            default:
                if (! $forceSent) {
                    $filter['gto_mail_sent_date'] = NULL;
                }
                break;
        }
    }

    /**
     * Add the filter for roud descriptions
     *
     * @param array $filter
     * @param string $roundDescription
     * @param int $trackId
     */
    protected function _addRoundsFilter(array &$filter, $roundDescription, $trackId = null)
    {
        $roundIds = $this->_getRoundIds($roundDescription, $trackId);
        if ($roundIds) {
            // Add or statement for round 0 for inserted rounds, and check if the description matches
            $filter[] = [
                'gto_id_round' => $roundIds,
                [
                    'gto_id_round' => 0,
                    'gto_round_description' => $roundDescription,
                    ],
                ];
        } else {
            // Only round 0 for inserted rounds, and check if the description matches
            $filter['gto_id_round']          = 0;
            $filter['gto_round_description'] = $roundDescription;
        }
    }

    /**
     * Special case: the staff only filter
     *
     * @param array $filter
     * @param string $fallbackMethod
     */
    protected function _addStaffFilter(array &$filter, $fallbackMethod)
    {
        $filter['ggp_staff_members'] = 1;
        if ('O' == $fallbackMethod) {
            $filter[] = 'gor_contact_email IS NOT NULL';
        }
    }

    /**
     * Add the receiver (to) fields filter
     *
     * @param array $filter
     * @param int $target
     * @param string $toMethod
     * @param string $fallbackMethod
     */
    protected function _addToFilter(array &$filter, $target, $toMethod, $fallbackMethod)
    {
        switch ($target) {
            case 3:
                // Staff
                return $this->_addStaffFilter($filter, $fallbackMethod);

            case 0:
                // Only relations and respondents
                break;

            case 1:
                // Only relations
                $filter[] = 'gto_id_relation <> 0';
                break;

            case 2:
                // Only respondents
                $filter[] = ['gto_id_relation' => 0, 'gto_id_relation IS NULL'];
                break;
        }

        $filter['ggp_staff_members'] = 0;

        switch ($toMethod) {
            case 'A':
                $filter['can_email'] = 1;
                break;
            case 'O':
                if ('O' == $fallbackMethod) {
                    $filter[] = [
                        'can_email' => 1,
                        'gor_contact_email IS NOT NULL',
                        ];
                }
                break;
            case 'F':
                if ('O' == $fallbackMethod) {
                    $filter[] = 'gor_contact_email IS NOT NULL';
                }
                break;
        }
    }

    /**
     * Get the id's for a certain round description
     *
     * @param string $roundDescription
     * @param int $trackId
     * @return array Of round id numbers
     */
    protected function _getRoundIds($roundDescription, $trackId = null)
    {
        $cacheId = __FUNCTION__;
        if ($trackId) {
             $cacheId .= '_' . $trackId;
            $sql     = "SELECT gro_id_round FROM gems__rounds
                WHERE gro_active = 1 AND gro_id_track = ? AND gro_round_description = ?";
            $binds[] = $trackId;
        } else {
            $sql     = "SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_round_description = ?";
        }
        $binds[] = $roundDescription;

        $cacheId .= '_' . \MUtil_String::toCacheId($roundDescription);

        return $this->_getSelectColCached($cacheId, $sql, $binds, ['round', 'rounds', 'track', 'tracks'], false);
    }

    /**
     * Get the active options for the model
     *
     * @return array
     */
    public function getActiveOptions()
    {
        return [
            0 => $this->_('Disabled'),
            1 => $this->_('Automatic'),
            2 => $this->_('Manually')
        ];
    }

    /**
     * Returns array (id => name) of all fillers (groups + relations) in all tracks, sorted by name
     *
     * @param $trackId TrajectId to filter
     * @param $target  -1 = Staff/Respondent/Relation, 0 = Respondent/Relation, 1 = Relation, 2 = Respondent, 3 = Staff
     *
     * @return array
     */
    public function getAllGroups($trackId = null, $target = -1)
    {
        if(is_null($trackId)) {
            $trackId = -1;
        }
        $trackId = (int) $trackId;

        $cacheId = str_replace(__CLASS__ . '_' . __FUNCTION__ . '_' . $trackId . 'x' . $target, '-', 'z');

        // When not only relation we include groups
        if ($target <> 1) {
            $sqlGroups = "SELECT DISTINCT ggp_name
                            FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                                INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                                INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                            WHERE ggp_group_active = 1 AND
                                gro_active=1 AND
                                gtr_active=1";
            if ($trackId > -1) {
                $sqlGroups .= $this->db->quoteInto(" AND gtr_id_track = ?", $trackId);
            }

            if ($target == 3) {
                // Only staff
                $sqlGroups .= $this->db->quoteInto(" AND ggp_staff_members = 1");
            } elseif ($target == 2) {
                // Only respondents
                $sqlGroups .= $this->db->quoteInto(" AND ggp_respondent_members = 1");
            }
        }

        // When relations included, load relation fields
        if ($target < 2) {
            $sqlRelations = "SELECT DISTINCT gtf_field_name as ggp_name
                            FROM gems__track_fields
                            WHERE gtf_field_type = 'relation'";
            if ($trackId > -1) {
                $sqlRelations .= $this->db->quoteInto(" AND gtf_id_track = ?", $trackId);
            }
        }

        switch ($target) {
            case -1:
            case 0:
                $sql = "SELECT ggp_name, ggp_name as label FROM ("
                . $sqlGroups .
                " UNION ALL " .
                $sqlRelations . "
                ) AS tmpTable";
                break;

            case 1:
                $sql = $sqlRelations;
                break;

            case 2:
            case 3:
                $sql = $sqlGroups;
                break;
        }

        $sql = $sql . " ORDER BY ggp_name";

        return $this->_getSelectPairsCached($cacheId, $sql, array(), 'tracks');
    }

    /**
     * The types of mail filters
     *
     * @return array
     */
    public function getBulkFilterOptions()
    {
        return array(
            'N' => $this->_('First mail'),
            'R' => $this->_('Reminder after first email'),
            'B' => $this->_('Before expiration'),
            'E' => $this->_('Reminder before expiration'),
        );
    }

    /**
     * Options for from address use.
     *
     * @return array
     */
    public function getBulkFromOptions()
    {
        $results['O'] = $this->_('Use organizational from address');

        if (isset($this->project->email['site']) && $this->project->email['site']) {
            $results['S'] = sprintf($this->_('Use site %s address'), $this->project->email['site']);
        }

        $results['U'] = $this->_("Use the 'By staff member' address");
        $results['F'] = $this->_('Other');

        return $results;
    }

    /**
     * The options for bulk mail token processing.
     *
     * @return array
     */
    public function getBulkProcessOptions()
    {
        return array(
            'M' => $this->_('Send multiple messages per respondent, one for each checked token.'),
            'O' => $this->_('Send one message per respondent, mark all checked tokens as sent.'),
            'A' => $this->_('Send one message per respondent, mark only mailed tokens as sent.'),
            );
    }

    /**
     * The options for bulk mail token processing.
     *
     * @return array
     */
    public function getBulkProcessOptionsShort()
    {
        return array(
            'M' => $this->_('Multiple messages'),
            'O' => $this->_('One message, mark all'),
            'A' => $this->_('One message'),
            );
    }

    /**
     * Options for standard to address use.
     *
     * @return array
     */
    public function getBulkToOptions()
    {
        $results['A'] = $this->_('Answerer (only)');
        $results['O'] = $this->_("Answerer or fallback if no email");
        $results['F'] = $this->_('Fallback (only)');

        return $results;
    }

    /**
     * The options for bulk mail token processing.
     *
     * @return array
     */
    public function getBulkTargetOptions()
    {
        return array(
            '0' => $this->_('Respondents and Relations'),
            '1' => $this->_('Relations'),
            '2' => $this->_('Respondents'),
            '3' => $this->_('Staff'),
            );
    }

    /**
     * The methods available for automatic communication
     *
     * @return array
     */
    public function getCommunicationMessengers()
    {
        $select = $this->db->select();
        $select->from('gems__comm_messengers', ['gcm_id_messenger', 'gcm_name'])
            ->where('gcm_active = 1')
            ->order('gcm_id_order');

        return $this->db->fetchPairs($select);
    }

    /**
     * Get all the job data
     *
     * @param $jobId
     * @return mixed
     */
    public function getJob($jobId)
    {
        $sql = $this->db->select()->from('gems__comm_jobs')
                        ->join('gems__comm_templates', 'gcj_id_message = gct_id_template')
                        ->join('gems__comm_messengers', 'gcj_id_communication_messenger = gcm_id_messenger')
                        ->where('gcj_active > 0')
                        ->where('gcj_id_job = ?', $jobId);

        return $this->db->fetchRow($sql);
    }

    /**
     * Get the filter to use on the tokenmodel when working with a mailjob.
     *
     * @param array $job
     * @param int $respondentId Optional, get for just one respondent
     * @param int $organizationId Optional, get for just one organization
     * @param boolean $forceSent Ignore previous sent mails
     * @return array
     */
    public function getJobFilter($job, $respondentId = null, $organizationId = null, $forceSent = false)
    {
        // Set up filter
        $filter = array(
            'gtr_active'          => 1,
            'gsu_active'          => 1,
            'grc_success'         => 1,
        	'gto_completion_time' => NULL,
        	'gto_valid_from <= CURRENT_TIMESTAMP',
            '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)'
        );

        if ($job['gcj_id_organization']) {
            if ($organizationId && ($organizationId !== $job['gcj_id_organization'])) {
                // Should never return any data
                return ['1=0'];
            }
            $filter['gto_id_organization'] = $job['gcj_id_organization'];
        } elseif ($organizationId) {
            $filter['gto_id_organization'] = $organizationId;
        }
        if ($respondentId) {
            $filter['gto_id_respondent'] = $respondentId;
        }
        if ($job['gcj_id_track']) {
            $filter['gto_id_track'] = $job['gcj_id_track'];
        }
        if ($job['gcj_round_description']) {
            $this->_addRoundsFilter($filter, $job['gcj_round_description'], $job['gcj_id_track']);
        }
        if ($job['gcj_id_survey']) {
            $filter['gto_id_survey'] = $job['gcj_id_survey'];
        }

        $this->_addModeFilter(
                $filter,
                $job['gcj_filter_mode'],
                intval($job['gcj_filter_days_between']),
                intval($job['gcj_filter_max_reminders']),
                $forceSent);

        $this->_addToFilter($filter, $job['gcj_target'], $job['gcj_to_method'], $job['gcj_fallback_method']);

        if (array_key_exists('gcj_target_group', $job) && $job['gcj_target_group']) {
            $filter[] = $this->db->quoteinto('(ggp_name = ? AND gto_id_relationfield IS NULL) or gtf_field_name = ?', $job['gcj_target_group']);
        }

        // \MUtil_Echo::track($filter);
        // \MUtil_Model::$verbose = true;

        return $filter;
    }

    /**
     * @param array $jobData
     * @return \Gems\Communication\JobMessenger\JobMessengerAbstract|null
     */
    public function getJobMessenger(array $jobData)
    {
        $messengerName = $jobData['gcm_type'];
        $messenger     = $this->loader->getCommunicationLoader()->getJobMessenger($messengerName);
        
        return $messenger;
    }

    /**
     * Get the filter to use on the tokenmodel when working with a mailjob.
     *
     * @return array job_id => description
     */
    public function getJobsOverview()
    {
        $fMode = "CASE ";
        foreach ($this->getBulkFilterOptions() as $key => $label) {
            $fMode .= "WHEN gcj_filter_mode = '$key' THEN '$label' ";
        }
        $fMode .= "ELSE '' END";

        $aMode = "CASE ";
        foreach ($this->getActiveOptions() as $key => $label) {
            $aMode .= "WHEN gcj_active = '$key' THEN '$label' ";
        }
        $aMode .= "ELSE '' END";

        $sql = sprintf(
                "SELECT gcj_id_job, CONCAT('%s', gcj_id_order, ' ', $fMode, ' ', $aMode)
                    FROM gems__comm_jobs ORDER BY gcj_id_order",
                $this->_('Order') . ' '
                );

        return $this->db->fetchPairs($sql);
        return $this->_getSelectPairsCached(__FUNCTION__, $sql);
    }

    /**
     * @param array $jobData
     * @param int   $respondentId Optional
     * @param int   $organizationId Optional
     * @param false $forceSent Ignore previous mails
     * @return mixed
     */
    public function getTokenData(array $jobData, $respondentId = null, $organizationId = null, $forceSent = false)
    {
        $filter = $this->getJobFilter($jobData, $respondentId, $organizationId, $forceSent);
        $model  = $this->loader->getTracker()->getTokenModel();

        // Fix for #680: token with the valid from the longest in the past should be the
        // used as first token and when multiple rounds start at the same date the
        // lowest round order should be used.
        $model->setSort(array('gto_valid_from' => SORT_ASC, 'gto_round_order' => SORT_ASC));

        // Prevent out of memory errors, only load the tokenid
        $model->trackUsage();
        $model->set('gto_id_token');

        return $model->load($filter);
    }
}
