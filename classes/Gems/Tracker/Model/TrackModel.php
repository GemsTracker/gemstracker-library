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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Simple stub for track model, allows extension by projects and adds auto labelling
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Model_TrackModel extends MUtil_Model_TableModel
{
    /**
     * Holds the trackData in array with key trackId, for internal caching use only
     *
     * @var array
     */
    protected $_trackData = array();

    /**
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * @var Gems_Util
     */
    protected $util;

    public function __construct()
    {
        parent::__construct('gems__tracks');

        Gems_Model::setChangeFieldsByPrefix($this, 'gtr');

        $this->set('gtr_date_start', 'default', new Zend_Date());
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @param boolean $detailed True when shopwing detailed information
     * @return Gems_Tracker_Model_TrackModel
     */
    public function applyFormatting($detailed = false)
    {
        $translated = $this->util->getTranslated();

        $this->resetOrder();

        $this->set('gtr_track_name',    'label', $this->translate->_('Name'));
        $this->set('gtr_track_class',   'label', $this->translate->_('Track Engine'), 'multiOptions', $this->tracker->getTrackEngineList($detailed));
        $this->set('gtr_survey_rounds', 'label', $this->translate->_('Surveys'));
       
        $this->set('gtr_active',        'label', $this->translate->_('Active'), 'multiOptions', $translated->getYesNo());
        $this->set('gtr_date_start',    'label', $this->translate->_('From'), 'dateFormat', $translated->dateFormatString, 'formatFunction', $translated->formatDate);
        $this->set('gtr_date_until',    'label', $this->translate->_('Use until'), 'dateFormat', $translated->dateFormatString, 'formatFunction', $translated->formatDateForever);
        
        $this->setIfExists('gtr_code',  'label', $this->translate->_('Code name'), 'size', 10, 'description', $this->translate->_('Only for programmers.'));

        if ($detailed) {
            $this->setIfExists('gtr_completed_event',
                'label', $this->translate->_('After completion'),
                'multiOptions', $this->loader->getEvents()->listTrackCompletionEvents());
        }

        return $this;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->tracker && $this->translate && $this->util;
    }

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @param int $trackId
     * @return Gems_Event_TrackCompletedEventInterface|null
     */
    public function getTrackCompletionEvent($trackId)
    {
        static $trackData = array();

        if (array_key_exists($trackId, $trackData)) {
            $track = $trackData[$trackId];
        } else {
            if ($track = $this->loadFirst(array('gtr_id_track' => $trackId))) {
                $trackData[$trackId] = $track;
            } else {
                $track = array();
            }
        }

        if (array_key_exists('gtr_completed_event', $track)) {
            if (!empty($track['gtr_completed_event'])) {
                return $this->loader->getEvents()->loadTrackCompletionEvent($track['gtr_completed_event']);
            }
        }
    }
}
