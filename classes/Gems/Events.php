<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

use Gems\Event\RespondentChangedEventInterface;

/**
 * Per project overruleable event processing engine
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Events extends \Gems_Loader_TargetLoaderAbstract
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

    /**
     * Each event type must implement an event class or interface derived
     * from EventInterface specified in this array.
     *
     * @see \Gems_Event_EventInterface
     *
     * @var array containing eventType => eventClass for all event classes
     */
    protected $_eventClasses = array(
        self::RESPONDENT_CHANGE_EVENT       => 'Gems\\Event\\RespondentChangedEventInterface',
        self::TRACK_CALCULATION_EVENT       => 'Gems_Event_TrackCalculationEventInterface',
        self::TRACK_COMPLETION_EVENT        => 'Gems_Event_TrackCompletedEventInterface',
        self::TRACK_BEFOREFIELDUPDATE_EVENT => 'Gems\\Event\\TrackBeforeFieldUpdateEventInterface',
        self::TRACK_FIELDUPDATE_EVENT       => 'Gems_Event_TrackFieldUpdateEventInterface',
        self::ROUND_CHANGED_EVENT           => 'Gems_Event_RoundChangedEventInterface',
        self::SURVEY_BEFORE_ANSWERING_EVENT => 'Gems_Event_SurveyBeforeAnsweringEventInterface',
        self::SURVEY_COMPLETION_EVENT       => 'Gems_Event_SurveyCompletedEventInterface',
        self::SURVEY_DISPLAY_EVENT          => 'Gems_Event_SurveyDisplayEventInterface',
    );

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Lookup event class for an event type. This class or interface should at the very least
     * implement the EventInterface.
     *
     * @see \Gems_Event_EventInterface
     *
     * @param string $eventType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function _getEventClass($eventType)
    {
        if (isset($this->_eventClasses[$eventType])) {
            return $this->_eventClasses[$eventType];
        } else {
            throw new \Gems_Exception_Coding("No event class exists for event type '$eventType'.");
        }
    }

    /**
     *
     * @param string $eventType An event subdirectory (may contain multiple levels split by '/'
     * @return array An array of type prefix => classname
     */
    protected function _getEventDirs($eventType)
    {
        $eventClass = str_replace('/', '_', $eventType);

        foreach ($this->_dirs as $name => $dir) {
            $prefix = $name . '_Event_'. $eventClass . '_';
            $paths[$prefix] = $dir . DIRECTORY_SEPARATOR . 'Event' . DIRECTORY_SEPARATOR . $eventType;
        }
        $paths[''] = APPLICATION_PATH . '/events/' . strtolower($eventType);
        // \MUtil_Echo::track($paths);

        return $paths;
    }

    /**
     * Returns a list of selectable events with an empty element as the first option.
     *
     * @param string $eventType The type (i.e. lookup directory with an associated class) of the events to list
     * @return \Gems_tracker_TrackerEventInterface or more specific a $eventClass type object
     */
    protected function _listEvents($eventType)
    {
        $classType = $this->_getEventClass($eventType);
        $paths     = $this->_getEventDirs($eventType);
        
        return $this->util->getTranslated()->getEmptyDropdownArray() + $this->listClasses($classType, $paths, 'getEventName');
    }

    /**
     * Loads and initiates an event class and returns the class (without triggering the event itself).
     *
     * @param string $eventName The class name of the individual event to load
     * @param string $eventType The type (i.e. lookup directory with an associated class) of the event
     * @return \Gems_tracker_TrackerEventInterface or more specific a $eventClass type object
     */
    protected function _loadEvent($eventName, $eventType)
    {
        $eventClass = $this->_getEventClass($eventType);

        // \MUtil_Echo::track($eventName);
        if (! class_exists($eventName, true)) {
            // Autoload is used for Zend standard defined classnames,
            // so if the class is not autoloaded, define the path here.
            $filename = APPLICATION_PATH . '/events/' . strtolower($eventType) . '/' . $eventName . '.php';

            if (! file_exists($filename)) {
                throw new \Gems_Exception_Coding("The event '$eventName' of type '$eventType' does not exist at location: $filename.");
            }
            // \MUtil_Echo::track($filename);

            include($filename);
        }

        $event = new $eventName();

        if (! $event instanceof $eventClass) {
            throw new \Gems_Exception_Coding("The event '$eventName' of type '$eventType' is not an instance of '$eventClass'.");
        }

        if ($event instanceof \MUtil_Registry_TargetInterface) {
            $this->applySource($event);
        }

        return $event;
    }

    /**
     *
     * @return array eventname => string
     */
    public function listRespondentChangedEvents()
    {
        return $this->_listEvents(self::RESPONDENT_CHANGE_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listRoundChangedEvents()
    {
        return $this->_listEvents(self::ROUND_CHANGED_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listSurveyBeforeAnsweringEvents()
    {
        return $this->_listEvents(self::SURVEY_BEFORE_ANSWERING_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listSurveyCompletionEvents()
    {
        return $this->_listEvents(self::SURVEY_COMPLETION_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listSurveyDisplayEvents()
    {
        return $this->_listEvents(self::SURVEY_DISPLAY_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackBeforeFieldUpdateEvents()
    {
        return $this->_listEvents(self::TRACK_BEFOREFIELDUPDATE_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackCalculationEvents()
    {
        return $this->_listEvents(self::TRACK_CALCULATION_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackCompletionEvents()
    {
        return $this->_listEvents(self::TRACK_COMPLETION_EVENT);
    }

    /**
     *
     * @return array eventname => string
     */
    public function listTrackFieldUpdateEvents()
    {
        return $this->_listEvents(self::TRACK_FIELDUPDATE_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems|Event\RespondentChangedEventInterface
     */
    public function loadRespondentChangedEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::RESPONDENT_CHANGE_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems_Event_RoundChangedEventInterface
     */
    public function loadRoundChangedEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::ROUND_CHANGED_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems_Event_SurveyBeforeAnsweringEventInterface
     */
    public function loadSurveyBeforeAnsweringEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::SURVEY_BEFORE_ANSWERING_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems_Event_SurveyCompletedEventInterface
     */
    public function loadSurveyCompletionEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::SURVEY_COMPLETION_EVENT);
    }


    /**
     *
     * @param string $eventName
     * @return \Gems_Event_SurveyDisplayEventInterface
     */
    public function loadSurveyDisplayEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::SURVEY_DISPLAY_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems\Event\TrackBeforeFieldUpdateEventInterface
     */
    public function loadBeforeTrackFieldUpdateEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::TRACK_BEFOREFIELDUPDATE_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems_Event_TrackCalculationEventInterface
     */
    public function loadTrackCalculationEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::TRACK_CALCULATION_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems_Event_TrackCompletedEventInterface
     */
    public function loadTrackCompletionEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::TRACK_COMPLETION_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return \Gems_Event_TrackFieldUpdateEventInterface
     */
    public function loadTrackFieldUpdateEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::TRACK_FIELDUPDATE_EVENT);
    }
}
