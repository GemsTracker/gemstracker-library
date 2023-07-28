<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda;

use Gems\Agenda\Repository\ActivityRepository;
use Gems\Agenda\Repository\FilterRepository;
use Gems\Agenda\Repository\LocationRepository;
use Gems\Agenda\Repository\ProcedureRepository;
use Gems\Agenda\Repository\StaffRepository;
use Gems\Cache\HelperAdapter;
use Gems\Db\ResultFetcher;
use Gems\Exception\Coding;
use Gems\Model\MetaModelLoader;
use Gems\Repository\OrganizationRepository;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\Tracker\RespondentTrack;
use Gems\User\Mask\MaskRepository;
use Laminas\Db\Sql\Join;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\TableIdentifier;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Agenda 
{
    /**
     *
     * @var \Gems\Agenda\Appointment[]
     */
    private array $_appointments = [];

    /**
     *
     * @var AppointmentFilterInterface[]
     */
    private array $_filters = [];

    /**
     *
     * @var string
     */
    public string $appointmentDisplayFormat = 'd-m-Y H:i';

    /**
     *
     * @var string
     */
    public string $episodeDisplayFormat = 'd-M-Y';
    
    /**
     * Sets the source of variables and the first directory for snippets
     */
    public function __construct(
        protected readonly ProjectOverloader $overloader,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly TranslatorInterface $translator,
        protected readonly HelperAdapter $cache,
        protected readonly ActivityRepository $activityRepository,
        protected readonly FilterRepository $filterRepository,

        protected readonly LocationRepository $locationRepository,
        protected readonly MaskRepository $maskRepository,
        protected readonly MetaModelLoader $metaModelLoader,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly ProcedureRepository $procedureRepository,
        protected readonly StaffRepository $staffRepository,

        protected readonly Tracker $tracker,
    )
    {
    }
    
    /**
     * Get the select statement for appointments in getAppointments()
     *
     * Allows for overruling on project level
     *
     * @return Select
     */
    protected function _getAppointmentSelect(): Select
    {
        $select = $this->resultFetcher->getSelect('gems__appointments');
        $select->join( 'gems__agenda_activities', 'gap_id_activity = gaa_id_activity', $select::SQL_STAR, $select::JOIN_LEFT)
                ->join('gems__agenda_procedures',  'gap_id_procedure = gapr_id_procedure', $select::SQL_STAR, $select::JOIN_LEFT)
                ->join('gems__locations',          'gap_id_location = glo_id_location', $select::SQL_STAR, $select::JOIN_LEFT)
                ->order('gap_admission_time DESC');

        return $select;
    }

    /**
     * Check if a track should be created for any of the filters
     *
     * @param AppointmentFilterInterface[] $filters
     * @param array $existingTracks Of $trackId => [RespondentTrack objects]
     * @param Tracker $tracker
     *
     * @return int Number of tokenchanges
     */
    protected function checkCreateTracksFromFilter(
        Appointment $appointment,
        array $filters,
        array $existingTracks,
        FilterTracer|null $filterTracer
    ): int
    {
        $tokenChanges = 0;

        // Check for tracks that should be created
        foreach ($filters as $filter) {
            if (!$filter->isCreator()) {
                continue;
            }

            $createTrack = true;

            // Find the method to use for this creator type
            $trackId     = $filter->getTrackId();
            $tracks      = array_key_exists($trackId, $existingTracks) ? $existingTracks[$trackId] : [];

            $respTrack = null;
            foreach($tracks as $respTrack) {
                /* @var $respTrack RespondentTrack */
                if (!$respTrack->hasSuccesCode()) {
                    continue;
                }

                $createTrack = $this->filterRepository->shouldCreateTrack($appointment, $filter, $respTrack, $filterTracer);
                if ($createTrack === false) {
                    break;  // Stop checking
                }
            }
            if ($filterTracer) {
                $filterTracer->addFilter($filter, $createTrack, $respTrack);

                if (! $filterTracer->executeChanges) {
                    $createTrack = false;
                }
            }

            // \MUtil\EchoOut\EchoOut::track($trackId, $createTrack, $filter->getName(), $filter->getSqlAppointmentsWhere(), $filter->getFilterId());
            if ($createTrack) {
                $engine = $this->tracker->getTrackEngine($filter->getTrackId());
                if (in_array($appointment->getOrganizationId(), $engine->getOrganizationIds())) {
                    $respTrack = $this->_createTrack($appointment, $filter);
                    $existingTracks[$trackId][] = $respTrack;

                    $tokenChanges += $respTrack->getCount();
                    if ($filterTracer) {
                        $filterTracer->addFilter($filter, $createTrack, $respTrack);
                    }
                }
            }
        }

        return $tokenChanges;
    }

    /**
     * Dynamically load and create a [Gems|Project]_Agenda_ class
     *
     * @param string $className
     * @param array $params
     * @return object
     */
    public function createAgendaClass(string $className, ...$params): object
    {
        if (!class_exists($className)) {
            $className = "Agenda\\$className";
        }
        return $this->overloader->create($className, ...$params);
    }

    /**
     * Create agenda select
     *
     * @param string|array $fields The appointment fields to select
     * @return LaminasAppointmentSelect
     */
    public function createAppointmentSelect(array|string $fields = '*'): LaminasAppointmentSelect
    {
        $select = new LaminasAppointmentSelect($this->resultFetcher, $this);
        $select->columns($fields);
        return $select;
    }

    /**
     * Create a new track for this appointment and the given filter
     *
     * @param AppointmentFilterInterface $filter
     * @param Tracker $tracker
     */
    protected function _createTrack(Appointment $appointment, AppointmentFilterInterface $filter): RespondentTrack
    {
        $trackData = [
            'gr2t_comment' => sprintf(
                $this->translator->_('Track created by %s filter'),
                $filter->getName()
            ),
        ];

        $fields    = [
            $filter->getFieldId() => $appointment->getId()
        ];
        $trackId   = $filter->getTrackId();
        $respTrack = $this->tracker->createRespondentTrack(
            $appointment->getRespondentId(),
            $appointment->getOrganizationId(),
            $trackId,
            null,
            $trackData,
            $fields
        );

        return $respTrack;
    }

    /**
     * Get all active respondents for this user
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param string $where Optional extra where statement
     * @return array appointmentId => appointment description
     */
    public function getActiveAppointments(int $respondentId, int $organizationId, string|null $patientNr = null, string|null $where = null): array
    {
        if ($where) {
            $where = "($where) AND ";
        } else {
            $where = "";
        }
        $where .= sprintf('gap_status IN (%s)', $this->getStatusKeysActiveDbQuoted());

        return $this->getAppointments($respondentId, $organizationId, $patientNr, $where);
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getActivities(int|null $organizationId = null): array
    {
        return $this->activityRepository->getActivityOptions($organizationId);
    }

    /**
     * Overrule this function to adapt the display of the agenda items for each project
     *
     * @see \Gems\Agenda\Appointment->getDisplayString()
     *
     * @param array $row Row containing result select
     * @return string
     */
    public function getAppointmentDisplay(array $row): string
    {
        $results[] = Model::reformatDate($row['gap_admission_time'], null, $this->appointmentDisplayFormat);
        if ($row['gaa_name']) {
            $results[] = $row['gaa_name'];
        }
        if ($row['gapr_name']) {
            $results[] = $row['gapr_name'];
        }
        if ($row['glo_name']) {
            $results[] = $row['glo_name'];
        }

        return implode($this->translator->_('; '), $results);
    }

    /**
     * Get an appointment object
     *
     * @param mixed $appointmentData Appointment id or array containing appointment data
     * @return \Gems\Agenda\Appointment
     */
    public function getAppointment(array|int $appointmentData): Appointment
    {
        if (! $appointmentData) {
            throw new Coding('Provide at least the apppointment id when requesting an appointment.');
        }

        if (is_array($appointmentData)) {
             if (!isset($appointmentData['gap_id_appointment'])) {
                 throw new Coding(
                         '$appointmentData array should atleast have a key "gap_id_appointment" containing the requested appointment id'
                         );
             }
            $appointmentId = $appointmentData['gap_id_appointment'];
        } else {
            $appointmentId = $appointmentData;
            $appointmentData = $this->getAppointmentData($appointmentId);
        }

        $this->_appointments[$appointmentId] = $this->overloader->create('Agenda\\Appointment', $appointmentData);

        return $this->_appointments[$appointmentId];
    }

    public function getAppointmentData(int $appointmentId): array
    {
        $select = $this->_getAppointmentSelect();
        $select->where(['gap_id_appointment' => $appointmentId]);

        $data = $this->resultFetcher->fetchRow($select);
        return $this->maskRepository->applyMaskToRow($data);
    }

    /**
     * Get all appointments for a respondent
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param string $where Optional extra where statement
     * @return array appointmentId => appointment description
     */
    public function getAppointments(int|null $respondentId, int $organizationId, string|null $patientNr = null, string|null $where = null): array
    {
        $select = $this->_getAppointmentSelect();

        if ($where) {
            $select->where($where);
        }

        if ($respondentId) {
            $select->where([
                'gap_id_user' => $respondentId,
                'gap_id_organization' => $organizationId,
            ]);
        } else {
            // Join might have been created in _getAppointmentSelect

            /**
             * @var Join|null
             */
            $joins = $select->getRawState(Select::JOINS);
            $addRespondentTable = true;
            if ($joins instanceof Join) {
                foreach($joins->getJoins() as $join) {
                    if ($join['name'] === 'gems__respondent2org'
                        || (is_array($join['name']) && in_array('gems__respondent2org', $join['name']))
                        || $join['name'] instanceof TableIdentifier && $join['name']->getTable() === 'gems__respondent2org') {
                        $addRespondentTable = false;
                    }
                }
            }

            if ($addRespondentTable) {
                $select->join(
                'gems__respondent2org',
                  'gap_id_user = gr2o_id_user AND gap_id_organization = gr2o_id_organization',
                      []
                );
            }

            $select->where('gr2o_patient_nr = ?', $patientNr)
                    ->where('gr2o_id_organization = ?', $organizationId);
        }

        // \MUtil\EchoOut\EchoOut::track($select->__toString());
        $rows = $this->resultFetcher->fetchAll($select);

        if (! $rows) {
            return [];
        }

        $results = [];
        foreach ($rows as $row) {
            $results[$row['gap_id_appointment']] = $this->getAppointmentDisplay($row);
        }
        return $results;
    }

    /**
     * Get all appointments for an episode
     *
     * @param int|EpisodeOfCare $episode Episode Id or object
     * @return array appointmentId => appointment object
     */
    public function getAppointmentsForEpisode(EpisodeOfCare|int $episode): array
    {
        $select = $this->_getAppointmentSelect();

        if ($episode instanceof EpisodeOfCare) {
            $episodeId = $episode->getId();
        } else {
            $episodeId = $episode;
        }
        $select->where(['gap_id_episode' => $episodeId]);

        // \MUtil\EchoOut\EchoOut::track($select->__toString());
        $rows = $this->resultFetcher->fetchAll($select);

        if (! $rows) {
            return [];
        }

        $results = [];
        foreach ($rows as $row) {
            $results[$row['gap_id_appointment']] = $this->getAppointment($row);
        }
        return $results;
    }

    /**
     * Get a selection list of coding systems used
     *
     * @return array code => label
     */
    public function getDiagnosisCodingSystems(): array
    {
        return [
            'DBC'    => $this->translator->_('DBC (Dutch Diagnosis Treatment Code)'),
            'manual' => $this->translator->_('Manual (organization specific)'),
        ];
    }

    /**
     * Get an appointment object
     *
     * @param mixed $episodeData Episode id or array containing episode data
     * @return EpisodeOfCare
     */
    public function getEpisodeOfCare(array|int $episodeData): EpisodeOfCare
    {
        if (! $episodeData) {
            throw new Coding('Provide at least the episode id when requesting an episode of care.');
        }

        if (is_array($episodeData)) {
             if (!isset($episodeData['gec_episode_of_care_id'])) {
                 throw new Coding(
                         '$episodeData array should atleast have a key "gec_episode_of_care_id" containing the requested episode id'
                         );
             }
        } else {
            $episodeData = $this->getEpisodeOfCareData($episodeData);
        }


        // \MUtil\EchoOut\EchoOut::track($appointmentId, $appointmentData);

        return $this->overloader->create('Agenda\\EpisodeOfCare', $episodeData);
    }

    public function getEpisodeOfCareData(int $episodeId): array
    {
        $select = $this->resultFetcher->getSelect('gems__episodes_of_care');
        $select->where(['gec_episode_of_care_id' => $episodeId]);

        return $this->resultFetcher->fetchRow($select);
    }

    /**
     * Get the status codes for all episode of care  items
     *
     * @return array code => label
     */
    public function getEpisodeStatusCodes(): array
    {
        $codes = $this->getEpisodeStatusCodesActive() +
                $this->getEpisodeStatusCodesInactive();

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for active episode of care items
     *
     * see https://www.hl7.org/fhir/episodeofcare.html
     *
     * @return array code => label
     */
    public function getEpisodeStatusCodesActive(): array
    {
        // A => active, C => Cancelled, E => Error, F => Finished, O => Onhold, P => Planned, W => Waitlist
        $codes = [
            'A' => $this->translator->_('Active'),
            'F' => $this->translator->_('Finished'),
            'O' => $this->translator->_('On hold'),
            'P' => $this->translator->_('Planned'),
            'W' => $this->translator->_('Waitlist'),
        ];

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for inactive episode of care items
     *
     * @return array code => label
     */
    public function getEpisodeStatusCodesInactive(): array
    {
        $codes = [
            'C' => $this->translator->_('Cancelled'),
            'E' => $this->translator->_('Erroneous input'),
        ];

        asort($codes);

        return $codes;
    }

    /**
     * Get the options list episodes for episodes
     *
     * @param array $episodes
     * @return array of $episodeId => Description
     */
    public function getEpisodesAsOptions(array $episodes): array
    {
        $options = [];

        foreach ($episodes as $id => $episode) {
            if ($episode instanceof EpisodeOfCare) {
                $options[$id] = $episode->getDisplayString();
            }
        }

        return $options;
    }

    /**
     * Get the episodes for a respondent
     *
     * @param \Gems\Tracker\Respondent $respondent
     * @param $where mixed Optional extra string or array filter
     * @return array of $episodeId => \Gems\Agenda\EpisodeOfCare
     */
    public function getEpisodesFor(Respondent $respondent, string|array $where = null): array
    {
        return $this->getEpisodesForRespId($respondent->getId(), $respondent->getOrganizationId(), $where);
    }

    /**
     * Get the episodes for a respondent
     *
     * @param int $respondentId
     * @param int $orgId
     * @param $where mixed Optional extra string or array filter
     * @return array of $episodeId => EpisodeOfCare
     */
    public function getEpisodesForRespId(int $respondentId, int $orgId, string|array|null $where = null): array
    {
        $select = $this->resultFetcher->getSelect('gems__episodes_of_care');
        $select->where([
            'gec_id_user' => $respondentId,
            'gec_id_organization' => $orgId,
        ])
            ->order('gec_startdate DESC');

        if ($where) {
            if (is_array($where)) {
                foreach ($where as $expr => $param) {
                    if (is_int($expr)) {
                        $select->where([$param]);
                    } else {
                        $select->where([$expr => $param]);
                    }
                }
            } else {
                $select->where($where);
            }
        }
        // \MUtil\EchoOut\EchoOut::track($select->__toString());

        $episodes = $this->resultFetcher->fetchAll($select);
        $output   = [];

        foreach ($episodes as $episodeData) {
            $episode = $this->getEpisodeOfCare($episodeData);
            $output[$episode->getId()] = $episode;
        }

        return $output;
    }

    /**
     * Load the list of assignable filters
     *
     * @return array filter_id => label
     */
    public function getFilterList(): array
    {
        $cacheId = HelperAdapter::cleanupForCacheId(__CLASS__ . '_' . __FUNCTION__);

        $output = $this->cache->getCacheItem($cacheId);
        if ($output) {
            return $output;
        }

        $output = $this->resultFetcher->fetchPairs("SELECT gaf_id, COALESCE(gaf_manual_name, gaf_calc_name) "
                . "FROM gems__appointment_filters WHERE gaf_active = 1 ORDER BY gaf_id_order");

        $this->cache->setCacheItem($cacheId, $output, ['appointment_filters']);

        return $output;
    }

    /**
     * Get the field names in appontments with their labels as the value
     *
     * @return array fieldname => label
     */
    public final function getFieldLabels(): array
    {
        $output = \MUtil\Ra::column('label', $this->getFieldData());

        asort($output);

        return $output;
    }

    /**
     * Get a structured nested array contain information on all the appointment
     *
     * @return array fieldname => array(label[, tableName, tableId, tableLikeFilter))
     */
    protected function getFieldData()
    {
        return [
            'gap_id_organization' => [
                'label' => $this->translator->_('Organization'),
                'tableName' => 'gems__organizations',
                'tableId' => 'gor_id_organization',
                'tableLikeFilter' => "gor_active = 1 AND gor_name LIKE '%s'",
            ],
            'gap_source' => [
                'label' => $this->translator->_('Source of appointment'),
            ],
            'gap_id_attended_by' => [
                'label' => $this->translator->_('With'),
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
            ],
            'gap_id_referred_by' => [
                'label' => $this->translator->_('Referrer'),
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
            ],
            'gap_id_activity' => [
                'label' => $this->translator->_('Activity'),
                'tableName' => 'gems__agenda_activities',
                'tableId' => 'gaa_id_activity',
                'tableLikeFilter' => "gaa_active = 1 AND gaa_name LIKE '%s'",
            ],
            'gap_id_procedure' => [
                'label' => $this->translator->_('Procedure'),
                'tableName' => 'gems__agenda_procedures',
                'tableId' => 'gapr_id_procedure',
                'tableLikeFilter' => "gapr_active = 1 AND gapr_name LIKE '%s'",
            ],
            'gap_id_location' => [
                'label' => $this->translator->_('Location'),
                'tableName' => 'gems__locations',
                'tableId' => 'glo_id_location',
                'tableLikeFilter' => "glo_active = 1 AND glo_name LIKE '%s'",
            ],
            'gap_subject' => [
                'label' => $this->translator->_('Subject'),
            ],
        ];
    }

    /**
     * Get a filter from the database
     *
     * @param $filterId string|int Id of a single filter
     * @return AppointmentFilterInterface|null or null
     */
    public function getFilter(int $filterId): AppointmentFilterInterface|null
    {
        return $this->filterRepository->getFilter($filterId);
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getHealthcareStaff($organizationId = null)
    {
        return $this->staffRepository->getAllStaffOptions($organizationId);
    }

    /**
     * Returns an array with identical key => value pairs containing care provision locations.
     *
     * @param int $orgId Optional to select for single organization
     * @return array
     */
    public function getLocations(int $orgId = null): array
    {
        $locations = $this->locationRepository->getActiveLocationsData($orgId);
        return array_column($locations,'glo_name', 'glo_id_location');
    }

    /**
     * Returns an array with identical key => value pairs containing care provision locations with organiation names
     *
     * @return array loId => label
     */
    public function getLocationsWithOrganization(): array
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->getCacheItem($cacheId)) {
            return $results;
        }

        $orgList   = $this->organizationRepository->getOrganizations();
        $locations = $this->locationRepository->getAllLocationData();
        $results   = [];
        if ($locations) {
            foreach ($locations as $location) {
                if ($location['glo_organizations']) {
                    $orgNames = [];
                    foreach ($location['glo_organizations'] as $orgId) {
                        if (isset($orgList[$orgId])) {
                            $orgNames[$orgId] = $orgList[$orgId];
                        }
                    }

                    $orgLabel = sprintf($this->translator->_(' (%s)'), implode($this->translator->_(', '), $orgNames));
                } else {
                    $orgLabel = '';
                }
                $results[$location['glo_id_location']] = $location['glo_name'] . $orgLabel;
            }
        }

        $this->cache->setCacheItem($cacheId, $results, ['locations']);
        return $results;
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getProcedures(int|null $organizationId = null): array
    {
        return $this->procedureRepository->getProcedureOptions($organizationId);
    }

    /**
     * Get the status codes for all active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodes(): array
    {
        $codes = $this->getStatusCodesActive() +
                $this->getStatusCodesInactive();

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesActive(): array
    {
        $codes = array(
            'AC' => $this->translator->_('Active appointment'),
            'CO' => $this->translator->_('Completed appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for inactive agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesInactive(): array
    {
        $codes = array(
            'AB' => $this->translator->_('Aborted appointment'),
            'CA' => $this->translator->_('Cancelled appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status keys for active agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysActive(): array
    {
        return array_keys($this->getStatusCodesActive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return \Zend_Db_Expr
     */
    public function getStatusKeysActiveDbQuoted(): \Zend_Db_Expr
    {
        $codes = [];

        $platform = $this->resultFetcher->getPlatform();

        foreach ($this->getStatusKeysActive() as $key) {
            $codes[] = $platform->quoteValue($key);
        }
        return new \Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the status keys for inactive agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysInactive(): array
    {
        return array_keys($this->getStatusCodesInactive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return \Zend_Db_Expr
     */
    public function getStatusKeysInactiveDbQuoted(): \Zend_Db_Expr
    {
        $codes = [];

        $platform = $this->resultFetcher->getPlatform();

        foreach ($this->getStatusKeysInactive() as $key) {
            $codes[] = $platform->quoteValue($key);
        }
        return new \Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the element that allows to create a track from an appointment
     *
     * When adding a new type, make sure to modify \Gems\Agenda\Appointment too
     * @see \Gems\Agenda\Appointment::getCreatorCheckMethod()
     *
     * @return array For element setting
     */
    public function getTrackCreateElement(): array
    {
        return [
            'elementClass' => 'Radio',
            'multiOptions' => $this->getTrackCreateOptions(),
            'label'        => $this->translator->_('When not assigned'),
            'onclick'      => 'this.form.submit();',
            ];
    }

    /**
     * Get the element that allows to create a track from an appointment
     *
     * When adding a new type, make sure to modify \Gems\Agenda\Appointment too
     * @see \Gems\Agenda\Appointment::getCreatorCheckMethod()
     *
     * @return array Code => label
     */
    public function getTrackCreateOptions(): array
    {
        return [
            0 => $this->translator->_('Do nothing'),
            4 => $this->translator->_('Create new on minimum start date difference'),
            3 => $this->translator->_('Create always (unless the appointment already assigned)'),
            2 => $this->translator->_('Create new on minimum end date difference'),
            5 => $this->translator->_('Create new when all surveys have been completed'),
            1 => $this->translator->_('Create new when all surveys have been completed and on minimum end date'),
            ];
    }
    /**
     * Get the type codes for agenda items
     *
     * @return array code => label
     */
    public function getTypeCodes(): array
    {
        return array(
            'A' => $this->translator->_('Ambulatory'),
            'E' => $this->translator->_('Emergency'),
            'F' => $this->translator->_('Field'),
            'H' => $this->translator->_('Home'),
            'I' => $this->translator->_('Inpatient'),
            'S' => $this->translator->_('Short stay'),
            'V' => $this->translator->_('Virtual'),
        );
    }

    /**
     * Returns true when the status code is active
     *
     * @param string $code
     * @return boolean
     */
    public function isStatusActive(string $code): bool
    {
        $stati = $this->getStatusCodesActive();

        return isset($stati[$code]);
    }

    /**
     * Load the filters from cache or elsewhere
     *
     * @return AppointmentFilterInterface[]
     */
    protected function loadDefaultFilters(): array
    {
        if ($this->_filters) {
            return $this->_filters;
        }

        $this->_filters = $this->filterRepository->getAllActivelyUsedFilters();

        return $this->_filters;
    }

    /**
     * Find an activity code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchActivity(string $name, int $organizationId, bool $create = true): int|null
    {
        return $this->activityRepository->matchActivity($name, $organizationId, $create);
    }

    /**
     *
     * @param mixed $to \Gems\Agenda\Appointment:EpsiodeOfCare
     * @return AppointmentFilterInterface[]
     */
    public function matchFilters(Appointment|EpisodeOfCare $to): array
    {
        $filters = $this->loadDefaultFilters();
        $output  = array();

        if ($to instanceof Appointment) {
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    if ($filter->matchAppointment($to)) {
                        $output[] = $filter;
                    }
                }
            }
        } elseif ($to instanceof EpisodeOfCare) {
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    if ($filter->matchEpisode($to)) {
                        $output[] = $filter;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Find a healt care provider for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int gas_id_staff staff id
     */
    public function matchHealthcareStaff(string $name, int $organizationId, bool $create = true): int|null
    {
        return $this->staffRepository->matchStaff($name, $organizationId, $create);
    }

    /**
     * Find a location for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return array location
     */
    public function matchLocation(string $name, int $organizationId, bool $create = true): int|null
    {
        return $this->locationRepository->matchLocation($name, $organizationId, $create);
    }

    /**
     * Find a procedure code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchProcedure(string $name, int $organizationId, bool $create = true): int|null
    {
        return $this->procedureRepository->matchProcedure($name, $organizationId, $create);
    }

    /**
     * Creates a new filter class object
     *
     * @param string $className The part after *_Agenda_Filter_
     * @return object
     */
    public function newFilterObject(string $className): object
    {
        return $this->overloader->create("Agenda\\Filter\\$className");
    }

    /**
     *
     * @return \Gems\Agenda\AppointmentFilterModel
     */
    public function newFilterModel(): AppointmentFilterModel
    {
        return $this->overloader->create("Agenda\\AppointmentFilterModel");
    }

    /**
     * Reset internally held data for testing
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->_appointments = [];
        $this->_filters = [];

        return $this;
    }

    public function updateTracksForAppointment(Appointment $appointment, FilterTracer|null $filterTracer = null): int
    {
        $tokenChanges = 0;

        // Find all the fields that use this agenda item
        $select = $this->resultFetcher->getSelect('gems__respondent2track2appointment');
        $select->columns(['gr2t2a_id_respondent_track'])
            ->join('gems__respondent2track', 'gr2t_id_respondent_track = gr2t2a_id_respondent_track', ['gr2t_id_track'])
            ->quantifier($select::QUANTIFIER_DISTINCT)
            ->order('gr2t_id_track');

        $nestedWhere = $select->where->nest();

        $nestedWhere->equalTo('gr2t2a_id_appointment', $appointment->getId());

        // AND find the filters for any new fields to fill
        $filters = $this->matchFilters($appointment);
        if ($filters) {
            $ids = array_map(function ($value) {
                return $value->getTrackId();
            }, $filters);

            $respId = $appointment->getRespondentId();
            $orgId  = $appointment->getOrganizationId();

            $nestedWhere->or->nest()
                ->equalTo('gr2t_id_user', $respId)
                ->equalTo('gr2t_id_organization', $orgId)
                ->in('gr2t_id_track', $ids)
                ->unnest();
        }

        $nestedWhere->unnest();

        // Now find all the existing tracks that should be checked
        $respTracks = $this->resultFetcher->fetchPairs($select);

        // \MUtil\EchoOut\EchoOut::track($respTracks);
        $existingTracks = [];
        if ($respTracks) {
            foreach ($respTracks as $respTrackId => $trackId) {
                $respTrack = $this->tracker->getRespondentTrack($respTrackId);

                // Recalculate this track
                $fieldsChanged = false;
                if ((! $filterTracer) || $filterTracer->executeChanges) {
                    $changed = $respTrack->recalculateFields($fieldsChanged);
                } else {
                    $changed = 0;
                }
                if ($filterTracer) {
                    $filterTracer->addTrackChecked($respTrack, $fieldsChanged, $changed);
                }
                $tokenChanges += $changed;

                // Store the track for creation checking
                $existingTracks[$trackId][] = $respTrack;
            }
        }

        // Only check if we need to create when this appointment is active and today or later
        if ($appointment->isActive() && ($appointment->getAdmissionTime()->getTimestamp() >= time())) {
            $tokenChanges += $this->checkCreateTracksFromFilter($appointment, $filters, $existingTracks, $filterTracer);
        } else {
            if ($filterTracer) {
                $filterTracer->setSkippedFilterCheck();
            }
        }

        return $tokenChanges;
    }
}
