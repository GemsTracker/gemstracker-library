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
    public $executeChanges = false;

    /**
     *
     * @var array
     */
    protected $filters;

    /**
     *
     * @var array
     */
    protected $tracks;

    /**
     *
     * @var boolean
     */
    protected $skippedFilterCheck = false;

    /**
     *
     * @var string
     */
    protected $skipMessage = '';

    /**
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param boolean $createTrack
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @return $this
     */
    public function addFilter(AppointmentFilterInterface $filter, $createTrack, \Gems_Tracker_RespondentTrack $respTrack = null)
    {
        $this->filters[$filter->getFilterId()] = [
            'filterName'  => $filter->getName(),
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
     * @param \Gems_Tracker_RespondentTrack $respTrack
     * @param boolean $fieldsChanged
     * @param int $tokensChanged
     * @return $this
     */
    public function addTrackChecked(\Gems_Tracker_RespondentTrack $respTrack, $fieldsChanged, $tokensChanged)
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
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     *
     * @return array respTrackId => [trackName, trackInfo, trackStart, fieldsChanged, tokensChanged]
     */
    public function getTracks()
    {
        return $this->tracks;
    }

    /**
     *
     * @return boolean
     */
    public function getSkippedFilterCheck()
    {
        return $this->skippedFilterCheck;
    }

    /**
     *
     * @param string $message
     * @return $this
     */
    public function setSkipCreationMessage($message)
    {
        $this->skipMessage = $message;

        return $this;
    }

    /**
     *
     * @param boolean $skip
     * @return $this
     */
    public function setSkippedFilterCheck($skip = true)
    {
        $this->skippedFilterCheck = true;

        return $this;
    }
}
