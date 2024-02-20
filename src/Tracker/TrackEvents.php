<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker;

use Gems\Cache\HelperAdapter;
use Gems\Exception\Coding;
use Gems\Tracker\TrackEvent\EventInterface;
use Gems\Tracker\TrackEvent\RespondentChangedEventInterface;
use Gems\Tracker\TrackEvent\RoundChangedEventInterface;
use Gems\Tracker\TrackEvent\SurveyBeforeAnsweringEventInterface;
use Gems\Tracker\TrackEvent\SurveyCompletedEventInterface;
use Gems\Tracker\TrackEvent\SurveyDisplayEventInterface;
use Gems\Tracker\TrackEvent\TrackBeforeFieldUpdateEventInterface;
use Gems\Tracker\TrackEvent\TrackCalculationEventInterface;
use Gems\Tracker\TrackEvent\TrackCompletedEventInterface;
use Gems\Tracker\TrackEvent\TrackFieldUpdateEventInterface;
use Gems\Util\Translated;
use Zalt\Loader\ProjectOverloader;

/**
 * Per project overruleable event processing engine
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TrackEvents
{
    const RESPONDENT_CHANGE_EVENT       = 'Respondent/Change';
    const TRACK_CALCULATION_EVENT       = 'Track/Calculate';
    const TRACK_COMPLETION_EVENT        = 'Track/Completed';
    const TRACK_BEFOREFIELDUPDATE_EVENT = 'Track/BeforeFieldUpdate';
    const TRACK_FIELDUPDATE_EVENT       = 'Track/FieldUpdate';
    const ROUND_CHANGED_EVENT           = 'Round/Changed';
    const SURVEY_BEFORE_ANSWERING_EVENT = 'Survey/BeforeAnswering';
    const SURVEY_COMPLETION_EVENT       = 'Survey/Completed';
    const SURVEY_DISPLAY_EVENT          = 'Survey/Display';

    protected array $config = [];

    public function __construct(
        protected readonly Translated $translatedUtil,
        protected readonly HelperAdapter $cache,
        protected readonly ProjectOverloader $overloader,
        array $config, )
    {
        if (isset($config['tracker'], $config['tracker']['trackEvents'])) {
            $this->config = $config['tracker']['trackEvents'];
        }
    }

    /**
     * Returns a list of selectable events with an empty element as the first option.
     *
     * @param string $eventType The type (i.e. lookup directory with an associated class) of the events to list
     * @return array
     */
    protected function _listEvents(string $eventType): array
    {
        $key = HelperAdapter::createCacheKey([get_called_class(), __FUNCTION__, $eventType]);
        if ($this->cache->hasItem($key)) {
            return $this->translatedUtil->getEmptyDropdownArray() + $this->cache->getCacheItem($key);
        }

        $trackEvents = $this->getTrackEventClasses($eventType);

        $eventList = [];
        if ($trackEvents) {
            foreach($trackEvents as $eventClassName) {
                $trackEvent = $this->_loadEvent($eventClassName, $eventType);
                $eventList[$eventClassName] = $trackEvent->getEventName() . " ({$eventClassName})";
            }
        }
        asort($eventList);

        $this->cache->setCacheItem($key, $eventList);

        return $this->translatedUtil->getEmptyDropdownArray() + $eventList;
    }

    /**
     * Loads and initiates an event class and returns the class (without triggering the event itself).
     *
     * @param string $eventName The class name of the individual event to load
     * @param string $eventType The type (i.e. lookup directory with an associated class) of the event
     * @return EventInterface or more specific a $eventClass type object
     */
    protected function _loadEvent(string $eventName, string $eventType): EventInterface
    {
        if (isset($this->config[$eventType]) && in_array($eventName, $this->config[$eventType])) {
            /**
             * @var EventInterface
             */
            return $this->overloader->create($eventName);
        }

        throw new Coding("The event '$eventName' of type '$eventType' could not be loaded.");
    }

    /**
     * @param string $eventType
     * @return string[]
     */
    protected function getTrackEventClasses(string $eventType): array
    {
        if (isset($this->config[$eventType])) {
            return $this->config[$eventType];
        }

        return [];
    }

    /**
     *
     * @return array eventname => string
     */
    public function listRespondentChangedEvents(): array
    {
        return $this->_listEvents(self::RESPONDENT_CHANGE_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listRoundChangedEvents(): array
    {
        return $this->_listEvents(self::ROUND_CHANGED_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listSurveyBeforeAnsweringEvents(): array
    {
        return $this->_listEvents(self::SURVEY_BEFORE_ANSWERING_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listSurveyCompletionEvents(): array
    {
        return $this->_listEvents(self::SURVEY_COMPLETION_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listSurveyDisplayEvents(): array
    {
        return $this->_listEvents(self::SURVEY_DISPLAY_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackBeforeFieldUpdateEvents(): array
    {
        return $this->_listEvents(self::TRACK_BEFOREFIELDUPDATE_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackCalculationEvents(): array
    {
        return $this->_listEvents(self::TRACK_CALCULATION_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackCompletionEvents(): array
    {
        return $this->_listEvents(self::TRACK_COMPLETION_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackFieldUpdateEvents(): array
    {
        return $this->_listEvents(self::TRACK_FIELDUPDATE_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return RespondentChangedEventInterface
     */
    public function loadRespondentChangedEvent($eventName): RespondentChangedEventInterface
    {
        return $this->_loadEvent($eventName, self::RESPONDENT_CHANGE_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return RoundChangedEventInterface
     */
    public function loadRoundChangedEvent($eventName): RoundChangedEventInterface
    {
        return $this->_loadEvent($eventName, self::ROUND_CHANGED_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return SurveyBeforeAnsweringEventInterface
     */
    public function loadSurveyBeforeAnsweringEvent($eventName): SurveyBeforeAnsweringEventInterface
    {
        return $this->_loadEvent($eventName, self::SURVEY_BEFORE_ANSWERING_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return SurveyCompletedEventInterface
     */
    public function loadSurveyCompletionEvent($eventName): SurveyCompletedEventInterface
    {
        return $this->_loadEvent($eventName, self::SURVEY_COMPLETION_EVENT);
    }


    /**
     *
     * @param string $eventName
     * @return SurveyDisplayEventInterface
     */
    public function loadSurveyDisplayEvent($eventName): SurveyDisplayEventInterface
    {
        return $this->_loadEvent($eventName, self::SURVEY_DISPLAY_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return TrackBeforeFieldUpdateEventInterface
     */
    public function loadBeforeTrackFieldUpdateEvent($eventName): TrackBeforeFieldUpdateEventInterface
    {
        return $this->_loadEvent($eventName, self::TRACK_BEFOREFIELDUPDATE_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return TrackCalculationEventInterface
     */
    public function loadTrackCalculationEvent($eventName): TrackCalculationEventInterface
    {
        return $this->_loadEvent($eventName, self::TRACK_CALCULATION_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return TrackCompletedEventInterface
     */
    public function loadTrackCompletionEvent($eventName): TrackCompletedEventInterface
    {
        return $this->_loadEvent($eventName, self::TRACK_COMPLETION_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return TrackFieldUpdateEventInterface
     */
    public function loadTrackFieldUpdateEvent($eventName): TrackFieldUpdateEventInterface
    {
        return $this->_loadEvent($eventName, self::TRACK_FIELDUPDATE_EVENT);
    }
}
