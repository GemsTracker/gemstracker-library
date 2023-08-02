<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda;

use Gems\Agenda\Filter\TrackFieldFilterCalculationInterface;
use Gems\Tracker\RespondentTrack;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 07-Jan-2020 12:00:56
 */
class FilterTracer
{
    /**
     *
     * @var boolean
     */
    public bool $executeChanges = false;

    /**
     *
     * @var array
     */
    protected array $filters;

    /**
     *
     * @var array
     */
    protected array $tracks;

    /**
     *
     * @var boolean
     */
    protected bool $skippedFilterCheck = false;

    /**
     *
     * @var string
     */
    protected string $skipMessage = '';

    /**
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param boolean $createTrack
     * @param RespondentTrack $respTrack
     * @return self
     */
    public function addFilter(TrackFieldFilterCalculationInterface $filter, bool $createTrack, RespondentTrack|null $respTrack = null): self
    {
        $appointmentFilter = $filter->getAppointmentFilter();
        $this->filters[$appointmentFilter->getFilterId()] = [
            'filterName'  => $appointmentFilter->getName(),
            'filterTrack' => $filter->getTrackId(),
            'filterField' => $filter->getFieldId(),
            'respTrackId' => $respTrack ? $respTrack->getRespondentTrackId() : null,
            'createTrack' => $createTrack,
            'skipMessage' => $this->skipMessage,
             ];

        $this->skipMessage = '';

        return $this;
    }

    /**
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param boolean $fieldsChanged
     * @param int $tokensChanged
     * @return $this
     */
    public function addTrackChecked(RespondentTrack $respTrack, bool $fieldsChanged, int $tokensChanged): self
    {
        $this->tracks[$respTrack->getRespondentTrackId()] = [
            'trackName'     => $respTrack->getTrackName(),
            'trackInfo'     => $respTrack->getFieldsInfo(),
            'trackStart'    => $respTrack->getStartDate(),
            'fieldsChanged' => $fieldsChanged,
            'tokensChanged' => $tokensChanged,
            ];

        return $this;
    }

    /**
     *
     * @return array filterId => [filterName, filterTrack, filterField, respTrackId, createTrack, skipMessage]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     *
     * @return array respTrackId => [trackName, trackInfo, trackStart, fieldsChanged, tokensChanged]
     */
    public function getTracks(): array
    {
        return $this->tracks;
    }

    /**
     *
     * @return boolean
     */
    public function getSkippedFilterCheck(): bool
    {
        return $this->skippedFilterCheck;
    }

    /**
     *
     * @param string $message
     * @return $this
     */
    public function setSkipCreationMessage(string $message): self
    {
        $this->skipMessage = $message;

        return $this;
    }

    /**
     *
     * @param boolean $skip
     * @return $this
     */
    public function setSkippedFilterCheck(bool $skip = true): self
    {
        $this->skippedFilterCheck = true;

        return $this;
    }
}
