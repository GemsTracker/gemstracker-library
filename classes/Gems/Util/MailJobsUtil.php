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
class MailJobsUtil extends UtilAbstract
{
    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @param array $filter
     * @param string $mode
     * @param int $daysBetween
     * @param int $maxReminders
     */
    protected function _addModeFilter(array &$filter, $mode, $daysBetween, $maxReminders)
    {
        switch ($mode) {
            case 'B':   // Reminder or first mail before expiry
                $filter[] = [
                    'gto_mail_sent_date' => null,
                    "gto_mail_sent_date < CURRENT_DATE() AND CURRENT_DATE() = DATE(DATE_SUB(gto_valid_until, INTERVAL $daysBetween DAY))",
                    ];
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
                $filter['gto_mail_sent_date'] = NULL;
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
        if ($trackId) {
            $cacheId = __FUNCTION__ . '_' . $trackId;
            $sql     = "SELECT gro_id_round FROM gems__rounds
                WHERE gro_active = 1 AND gro_id_track = ? AND gro_round_description = ?";
            $binds[] = $trackId;
        } else {
            $cacheId = __FUNCTION__;
            $sql     = "SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_round_description = ?";
        }
        $binds[] = $roundDescription;

        return $this->_getSelectColCached($cacheId, $sql, $binds, ['round', 'rounds', 'track', 'tracks'], false);
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
            'M' => $this->_('Send multiple mails per respondent, one for each checked token.'),
            'O' => $this->_('Send one mail per respondent, mark all checked tokens as sent.'),
            'A' => $this->_('Send one mail per respondent, mark only mailed tokens as sent.'),
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
            'M' => $this->_('Multiple emails'),
            'O' => $this->_('One mail, mark all'),
            'A' => $this->_('One mail'),
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
     * Get the filter to use on the tokenmodel when working with a mailjob.
     *
     * @param array $job
     * @param int $respondentId Optional, get for just one respondent
     * @param int $organizationId Optional, get for just one organization
     * @return array
     */
    public function getJobFilter($job, $respondentId = null, $organizationId = null)
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
                intval($job['gcj_filter_max_reminders']));

        $this->_addToFilter($filter, $job['gcj_target'], $job['gcj_to_method'], $job['gcj_fallback_method']);

        // \MUtil_Echo::track($filter);
        // \MUtil_Model::$verbose = true;

        return $filter;
    }
}
