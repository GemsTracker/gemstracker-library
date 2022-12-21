<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Task\TaskRunnerBatch;
use Gems\Tracker;
use Mezzio\Session\SessionInterface;
use MUtil\Batch\BatchAbstract;
use MUtil\Translate\Translator;
use Psr\Log\LoggerInterface;
use Zalt\Loader\ProjectOverloader;

class CommJobRepository
{
    protected int $currentUserId;

    protected ResultFetcher $resultFetcher;

    public function __construct(
        protected CachedResultFetcher $cachedResultFetcher,
        protected Translator $translator,
        protected Tracker $tracker,
        CurrentUserRepository $currentUserRepository
    )
    {
        $this->resultFetcher = $this->cachedResultFetcher->getResultFetcher();
        $this->currentUserId = $currentUserRepository->getCurrentUser()->getUserId();
    }

    public function getActiveOptions(): array
    {
        return [
            0 => $this->translator->_('Disabled'),
            1 => $this->translator->_('Automatic'),
            2 => $this->translator->_('Manually'),
        ];
    }

    /**
     *
     * @param array $filter
     * @param string $mode
     * @param int $daysBetween
     * @param int $maxReminders
     * @param boolean $forceSent Ignore previous sent mails
     */
    protected function addModeFilter(array &$filter, string $mode, int $daysBetween, int $maxReminders, bool $forceSent = false): void
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
    protected function addRoundsFilter(array &$filter, string $roundDescription, ?int $trackId = null): void
    {
        $roundIds = $this->getRoundIds($roundDescription, $trackId);
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
    protected function _addStaffFilter(array &$filter, string $fallbackMethod): void
    {
        $filter['ggp_member_type'] = 'staff';
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
    protected function addToFilter(array &$filter, int $target, string $toMethod, string $fallbackMethod): void
    {
        switch ($target) {
            case 3:
                // Staff
                $this->_addStaffFilter($filter, $fallbackMethod);

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

        $filter[] = 'ggp_member_type != \'staff\'';

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

    public function getAllGroups(?int $trackId = null, int $target = -1): array
    {
        if(is_null($trackId)) {
            $trackId = -1;
        }
        $trackId = (int) $trackId;

        $cacheId = str_replace(__CLASS__ . '_' . __FUNCTION__ . '_' . $trackId . 'x' . $target, '-', 'z');

        // When not only relation we include groups

        $params = [];
        if ($target <> 1) {
            $sqlGroups = "SELECT DISTINCT ggp_name
                            FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                                INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                                INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                            WHERE ggp_group_active = 1 AND
                                gro_active=1 AND
                                gtr_active=1";
            if ($trackId > -1) {
                $sqlGroups .= ' AND gtr_id_track = ?';
                $params[] = $trackId;
            }

            if ($target == 3) {
                // Only staff
                $sqlGroups .= " AND ggp_member_type = 'staff'";
            } elseif ($target == 2) {
                // Only respondents
                $sqlGroups .= " AND ggp_member_type = 'respondent'";
            }
        }

        // When relations included, load relation fields
        if ($target < 2) {
            $sqlRelations = "SELECT DISTINCT gtf_field_name as ggp_name
                            FROM gems__track_fields
                            WHERE gtf_field_type = 'relation'";
            if ($trackId > -1) {
                $sqlRelations .= " AND gtf_id_track = ?";
                $params[] = $trackId;
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

        return $this->cachedResultFetcher->fetchPairs($cacheId, $sql, $params, ['tracks']);
    }

    /**
     * The types of mail filters
     *
     * @return array
     */
    public function getBulkFilterOptions(): array
    {
        return array(
            'N' => $this->translator->_('First mail'),
            'R' => $this->translator->_('Reminder after first email'),
            'B' => $this->translator->_('Before expiration'),
            'E' => $this->translator->_('Reminder before expiration'),
        );
    }

    /**
     * Options for from address use.
     *
     * @return array
     */
    public function getBulkFromOptions(): array
    {
        $results['O'] = $this->translator->_('Use organizational from address');

        if (isset($this->project->email['site']) && $this->project->email['site']) {
            $results['S'] = sprintf($this->translator->_('Use site address'));
        }

        $results['U'] = $this->translator->_("Use the 'By staff member' address");
        $results['F'] = $this->translator->_('Other');

        return $results;
    }

    /**
     * Options for standard to address use.
     *
     * @return array
     */
    public function getBulkToOptions(): array
    {
        $results['A'] = $this->translator->_('Answerer (only)');
        $results['O'] = $this->translator->_('Answerer or fallback if no email');
        $results['F'] = $this->translator->_('Fallback (only)');

        return $results;
    }

    /**
     * The options for bulk mail token processing.
     *
     * @return array
     */
    public function getBulkProcessOptions(): array
    {
        return [
            'M' => $this->translator->_('Send multiple messages per respondent, one for each checked token.'),
            'O' => $this->translator->_('Send one message per respondent, mark all checked tokens as sent.'),
            'A' => $this->translator->_('Send one message per respondent, mark only mailed tokens as sent.'),
        ];
    }

    /**
     * The options for bulk mail token processing.
     *
     * @return array
     */
    public function getBulkProcessOptionsShort(): array
    {
        return array(
            'M' => $this->translator->_('Multiple messages'),
            'O' => $this->translator->_('One message, mark all'),
            'A' => $this->translator->_('One message'),
        );
    }

    /**
     * The options for bulk mail token processing.
     *
     * @return array
     */
    public function getBulkTargetOptions(): array
    {
        return array(
            0 => $this->translator->_('Respondents and Relations'),
            1 => $this->translator->_('Relations'),
            2 => $this->translator->_('Respondents'),
            3 => $this->translator->_('Staff'),
        );
    }

    public function getCommunicationMessengers(): array
    {
        $select = $this->resultFetcher->getSelect('gems__comm_messengers');
        $select->columns([
            'gcm_id_messenger',
            'gcm_name',
        ])->where([
           'gcm_active' => 1,
        ])->order([
            'gcm_id_order',
        ]);

        return $this->resultFetcher->fetchPairs($select);
    }

    /**
     * Return the available Comm templates.
     *
     * @staticvar array $data
     * @return array The templateId => subject list
     */
    public function getCommTemplates(?string $mailTarget = null): array
    {
        static $data;

        if (! $data) {
            $select = $this->resultFetcher->getSelect('gems__comm_templates');
            $select->columns([
                'gct_id_template',
                'gct_name',
            ]);

            if ($mailTarget) {
                $select->where(['gct_target' => $mailTarget]);
            }
            $select->order(['gct_name']);

            $data = $this->resultFetcher->fetchPairs($select);
        }

        return $data;
    }

    /**
     * Perform automatic job mail
     */
    public function getCronBatch(string $id, ProjectOverloader $loader, SessionInterface $session, ?LoggerInterface $cronLog = null): BatchAbstract
    {
        $batch = new TaskRunnerBatch($id, $loader, $session);
        if ($cronLog) {
            $batch->setMessageLogger($cronLog);
        }
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (! $batch->isLoaded()) {
            $this->loadCronBatch($batch);
        }

        return $batch;
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
    public function getJobFilter(array $job, ?int $respondentId = null, ?int $organizationId = null, bool $forceSent = false)
    {
        // Set up filter
        $filter = [
            'gtr_active'          => 1,
            'gsu_active'          => 1,
            'grc_success'         => 1,
            'gto_completion_time' => NULL,
            'gto_valid_from <= CURRENT_TIMESTAMP',
            '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)'
        ];

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
            $this->addRoundsFilter($filter, $job['gcj_round_description'], $job['gcj_id_track']);
        }
        if ($job['gcj_id_survey']) {
            $filter['gto_id_survey'] = $job['gcj_id_survey'];
        }

        $this->addModeFilter(
            $filter,
            $job['gcj_filter_mode'],
            intval($job['gcj_filter_days_between']),
            intval($job['gcj_filter_max_reminders']),
            $forceSent);

        $this->addToFilter($filter, $job['gcj_target'], $job['gcj_to_method'], $job['gcj_fallback_method']);

        $groups = $this->getAllGroups();

        if (array_key_exists('gcj_target_group', $job) && $job['gcj_target_group'] && in_array($job['gcj_target_group'], $groups)) {
            $filter[] = sprintf('(ggp_name = ? AND gto_id_relationfield IS NULL) or gtf_field_name = %s', $job['gcj_target_group']);
        }

        // \MUtil\EchoOut\EchoOut::track($filter);
        // \MUtil\Model::$verbose = true;

        return $filter;
    }

    /**
     * Get the id's for a certain round description
     *
     * @param string $roundDescription
     * @param int $trackId
     * @return array Of round id numbers
     */
    protected function getRoundIds(string $roundDescription, ?int $trackId = null)
    {
        $cacheId = __FUNCTION__;
        $binds = [];
        if ($trackId) {
            $cacheId .= '_' . $trackId;
            $sql     = "SELECT gro_id_round FROM gems__rounds
                WHERE gro_active = 1 AND gro_id_track = ? AND gro_round_description = ?";
            $binds[] = $trackId;
        } else {
            $sql     = "SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_round_description = ?";
        }
        $binds[] = $roundDescription;

        $cacheId .= '_' . $roundDescription;

        return $this->cachedResultFetcher->fetchCol($cacheId, $sql, $binds, [
            'round',
            'rounds',
            'track',
            'tracks',
        ]);
    }

    /**
     * Perform the actions and load the tasks needed to start the cron batch
     *
     * @param TaskRunnerBatch $batch
     */
    protected function loadCronBatch(TaskRunnerBatch $batch)
    {
        $batch->addMessage(sprintf($this->translator->_("Starting mail jobs")));
        $batch->addTask('Mail\\AddAllMailJobsTask');

        // Check for unprocessed tokens,
        $this->tracker->loadCompletedTokensBatch($batch, null, $this->currentUserId);
    }
}