<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Per project overruleable event processing engine
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Events extends Gems_Loader_TargetLoaderAbstract
{
    const EVENTS_DIR              = 'events';

    const ROUND_CHANGED_EVENT           = 'round/changed';
    const SURVEY_BEFORE_ANSWERING_EVENT = 'survey/beforeanswering';
    const SURVEY_COMPLETION_EVENT       = 'survey/completed';

    /**
     * Each event type must implement an event class or interface derived
     * from EventInterface specified in this array.
     *
     * @see Gems_Event_EventInterface
     *
     * @var array containing eventType => eventClass for all event classes
     */
    protected $_eventClasses = array(
        self::ROUND_CHANGED_EVENT           => 'Gems_Event_RoundChangedEventInterface',
        self::SURVEY_BEFORE_ANSWERING_EVENT => 'Gems_Event_SurveyBeforeAnsweringEventInterface',
        self::SURVEY_COMPLETION_EVENT       => 'Gems_Event_SurveyCompletedEventInterface',
    );

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Lookup event class for an event type. This class or interfce should at the very least
     * implement the EventInterface.
     *
     * @see Gems_Event_EventInterface
     *
     * @param string $eventType The type (i.e. lookup directory) to find the associated class for
     * @return string Class/interface name associated with the type
     */
    protected function _getEventClass($eventType)
    {
        if (isset($this->_eventClasses[$eventType])) {
            return $this->_eventClasses[$eventType];
        } else {
            throw new Gems_Exception_Coding("No event class exists for event type '$eventType'.");
        }
    }

    /**
     * Returns a list of selectable events with an empty element as the first option.
     *
     * @param string $eventType The type (i.e. lookup directory with an associated class) of the events to list
     * @return Gems_tracker_TrackerEventInterface or more specific a $eventClass type object
     */
    protected function _listEvents($eventType)
    {
        $path       = APPLICATION_PATH . '/' . self::EVENTS_DIR . '/' . $eventType;
        $results    = $this->util->getTranslated()->getEmptyDropdownArray();
        $eventClass = $this->_getEventClass($eventType);

        if (file_exists($path)) {
            $eDir = dir($path);

            while (false !== ($filename = $eDir->read())) {
                if ('.php' === substr($filename, -4)) {
                    $eventName = substr($filename, 0, -4);

                    if (! class_exists($eventName)) {
                        include($path . '/' . $filename);
                    }

                    $event = new $eventName();

                    if ($event instanceof $eventClass) {
                        if ($event instanceof MUtil_Registry_TargetInterface) {
                            $this->applySource($event);
                        }

                        $results[$eventName] = $event->getEventName();
                    }
                    // MUtil_Echo::track($eventName);
                }
            }
        }
        return $results;
    }

    /**
     * Loads and initiates an event class and returns the class (without triggering the event itself).
     *
     * @param string $eventName The class name of the individual event to load
     * @param string $eventType The type (i.e. lookup directory with an associated class) of the event
     * @return Gems_tracker_TrackerEventInterface or more specific a $eventClass type object
     */
    protected function _loadEvent($eventName, $eventType)
    {
        $eventClass = $this->_getEventClass($eventType);

        if (! class_exists($eventName)) {
            $filename = APPLICATION_PATH . '/' . self::EVENTS_DIR . '/' . $eventType . '/' . $eventName . '.php';

            if (! file_exists($filename)) {
                throw new Gems_Exception_Coding("The event '$eventName' of type '$eventType' does not exist at location: $filename.");
            }

            include($filename);
        }

        $event = new $eventName();

        if (! $event instanceof $eventClass) {
            throw new Gems_Exception_Coding("The event '$eventName' of type '$eventType' is not an instance of '$eventClass'.");
        }

        if ($event instanceof MUtil_Registry_TargetInterface) {
            $this->applySource($event);
        }

        return $event;
    }

    /**
     *
     * @return Gems_Event_RoundChangedEventInterface
     */
    public function listRoundChangedEvents()
    {
        return $this->_listEvents(self::ROUND_CHANGED_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return Gems_Event_RoundChangedEventInterface
     */
    public function loadRoundChangedEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::ROUND_CHANGED_EVENT);
    }

    /**
     *
     * @return Gems_Event_SurveyCompletedEventInterface
     */
    public function listSurveyBeforeAnsweringEvents()
    {
        return $this->_listEvents(self::SURVEY_BEFORE_ANSWERING_EVENT);
    }

    /**
     *
     * @return Gems_Event_SurveyCompletedEventInterface
     */
    public function listSurveyCompletionEvents()
    {
        return $this->_listEvents(self::SURVEY_COMPLETION_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return Gems_Event_SurveyBeforeAnsweringEventInterface
     */
    public function loadSurveyBeforeAnsweringEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::SURVEY_BEFORE_ANSWERING_EVENT);
    }

    /**
     *
     * @param string $eventName
     * @return Gems_Event_SurveyCompletedEventInterface
     */
    public function loadSurveyCompletionEvent($eventName)
    {
        return $this->_loadEvent($eventName, self::SURVEY_COMPLETION_EVENT);
    }
}
