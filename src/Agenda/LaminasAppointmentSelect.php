<?php

namespace Gems\Agenda;

use DateTimeInterface;
use Gems\Db\ResultFetcher;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use MUtil\Model;
use Gems\Agenda\Filter\AppointmentFilterInterface;

class LaminasAppointmentSelect
{
    protected array $columns = [Select::SQL_STAR];

    protected Select $select;

    public function __construct(
        protected readonly ResultFetcher $resultFetcher,
        protected readonly Agenda $agenda,
    )
    {
        $this->select = $this->resultFetcher->getSelect('gems__appointments');
        $this->columns();
    }

    public function columns(string|array $fields = '*'): self
    {
        if ($fields === '*') {
            $this->columns = [Select::SQL_STAR];
            $fields = [Select::SQL_STAR];
        }
        $this->select->columns($fields);

        return $this;
    }

    public function fetchAll(): array
    {
        return $this->resultFetcher->fetchAll($this->select);
    }

    public function fetchOne(): mixed
    {
        return $this->resultFetcher->fetchOne($this->select);
    }

    public function fetchRow(): array
    {
        return $this->resultFetcher->fetchRow($this->select);
    }

    /**
     * For a certain appointment filter
     *
     * Add's the filter sql where and remembers the filter
     *
     * @param AppointmentFilterInterface $filter
     * @return self
     */
    public function forFilter(AppointmentFilterInterface $filter): self
    {
        $this->select->where($filter->getSqlAppointmentsWhere());

        return $this;
    }

    /**
     * For a certain appointment filter
     *
     * @param int $filterId
     * @return self
     */
    public function forFilterId(int $filterId): self
    {
        $filter = $this->agenda->getFilter($filterId);
        if ($filter) {
            return $this->forFilter($filter);
        }

        return $this;
    }

    /**
     *
     * @param DateTimeInterface $from Optional date after which the appointment must occur
     * @param DateTimeInterface $until Optional date before which the appointment must occur
     * @param boolean $sortAsc Retrieve first or last appointment first
     * @return self
     */
    public function forPeriod(DateTimeInterface|null $from = null, DateTimeInterface|null $until = null, bool $sortAsc = true): self
    {
        if ($from) {
            $this->select->where->greaterThanOrEqualTo('gap_admission_time', $from->format(Model::getTypeDefault(Model::TYPE_DATETIME, 'storageFormat')));
        }
        if ($until) {
            $this->select->where->lessThanOrEqualTo('gap_admission_time', $from->format(Model::getTypeDefault(Model::TYPE_DATETIME, 'storageFormat')));
        }
        if ($sortAsc) {
            $this->order('gap_admission_time ASC');
        } else {
            $this->order('gap_admission_time DESC');
        }

        return $this;
    }

    /**
     * For a certain respondent / organization
     *
     * @param int $respondentId
     * @param int $organizationId
     * @return self
     */
    public function forRespondent(int $respondentId, int $organizationId): self
    {
        $this->select->where([
            'gap_id_user' => $respondentId,
            'gap_id_organization' => $organizationId,

        ]);

        return $this;
    }

    /**
     * @return Select
     */
    public function getSelect(): Select
    {
        return $this->select;
    }

    /**
     * Select only active agenda items
     *
     * @return self
     */
    public function onlyActive(): self
    {
        $this->select->where([
            'gap_status' => $this->agenda->getStatusKeysActive(),
        ]);

        return $this;
    }

    /**
     *
     * @param string|array|Expression $spec The column(s) and direction to order by.
     * @return self
     */
    public function order(string|array|Expression $spec): self
    {
        $this->select->order($spec);

        return $this;
    }

    /**
     * Add a filter for the appointment id's currently used in tracks with this track id
     *
     * @param int $trackId The current track id
     * @param int $respTrackId The current respondent track id or null for new tracks
     * @param array $previousAppIds array of gap_id_appointment
     */
    public function uniqueForTrackId(int $trackId, int|null $respTrackId = null, array|null $previousAppIds = null): self
    {
        if ($previousAppIds) {
            // When unique for all tracks of this type the current track
            // appointment id's should also be excluded.
            $this->uniqueInTrackInstance($previousAppIds);
        }

        $sql = "gap_id_appointment NOT IN
                    (SELECT gr2t2a_id_appointment FROM gems__respondent2track2appointment
                        INNER JOIN gems__respondent2track
                            ON gr2t2a_id_respondent_track = gr2t_id_respondent_track
                        WHERE gr2t2a_id_appointment IS NOT NULL AND
                            gr2t_id_track = $trackId";

        if ($respTrackId) {
            // Exclude all fields of the current respondent track as it is being recalculated
            // and therefore they may have changed.
            //
            // Instead we filter here on $previousAppIds
            $sql .= " AND NOT (gr2t2a_id_respondent_track = $respTrackId)";
        }

        $sql .= ")";

        $this->select->where([$sql]);

        return $this;
    }

    /**
     * Add a filter for the appointment id's currently used in this track
     *
     * @param array $previousAppIds array of gap_id_appointment
     */
    public function uniqueInTrackInstance(array $previousAppIds): self
    {
        if ($previousAppIds) {
            // Exclude the current app id's in the track
            $this->select->where->notIn('gap_id_appointment', $previousAppIds);
        }
        return $this;
    }


}