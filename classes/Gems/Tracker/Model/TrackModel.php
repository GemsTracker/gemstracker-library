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

    /**
     * Construct a track model
     */
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
        $translator = $this->getTranslateAdapter();

        $this->resetOrder();

        $this->set('gtr_track_name',    'label', $translator->_('Name'));
        $this->set('gtr_track_class',   'label', $translator->_('Track Engine'),
                'multiOptions', $this->tracker->getTrackEngineList($detailed));
        $this->set('gtr_survey_rounds', 'label', $translator->_('Surveys'));

        $this->set('gtr_active',        'label', $translator->_('Active'),
                'multiOptions', $translated->getYesNo());
        $this->set('gtr_date_start',    'label', $translator->_('From'),
                'dateFormat', $translated->dateFormatString,
                'formatFunction', $translated->formatDate);
        $this->set('gtr_date_until',    'label', $translator->_('Use until'),
                'dateFormat', $translated->dateFormatString,
                'formatFunction', $translated->formatDateForever);

        $this->setIfExists('gtr_code',  'label', $translator->_('Code name'),
                'size', 10,
                'description', $translator->_('Only for programmers.'));

        if ($detailed) {
            $events = $this->loader->getEvents();

            $list = $events->listTrackCalculationEvents();
            if (count($list) > 1) {
                $this->setIfExists('gtr_calculation_event',
                    'label', $translator->_('Before (re)calculation'),
                    'multiOptions', $list);
            }

            $list = $events->listTrackCompletionEvents();
            if (count($list) > 1) {
                $this->setIfExists('gtr_completed_event',
                    'label', $translator->_('After completion'),
                    'multiOptions', $list);
            }
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
     * When this method returns something other than an empty array it will try
     * to add fields to a newly created track
     * 
     * @return array Should be an array or arrays, containing the default fields
     */
    public function getDefaultFields()
    {
        /*
        $defaultFields = array(
            array(
                'gtf_field_name'        => 'Treatment',
                'gtf_field_code'        => 'treatment',
                'gtf_field_description' => 'Enter the shorthand code for the treatment here',
                'gtf_field_values'      => null,
                'gtf_field_type'        => 'text',
                'gtf_required'          => 1,
                'gtf_readonly'          => 0
            ),
            array(
                'gtf_field_name'        => 'Physician',
                'gtf_field_code'        => 'physicion',
                'gtf_field_description' => '',
                'gtf_field_values'      => null,
                'gtf_field_type'        => 'text',
                'gtf_required'          => 0,
                'gtf_readonly'          => 0
            )
        );
        */
        
        $defaultFields = array();
        
        return $defaultFields;
    }

    /**
     * Returns a translate adaptor
     *
     * @return Zend_Translate_Adapter
     */
    protected function getTranslateAdapter()
    {
        if ($this->translate instanceof Zend_Translate)
        {
            return $this->translate->getAdapter();
        }

        if (! $this->translate instanceof Zend_Translate_Adapter) {
            $this->translate = new MUtil_Translate_Adapter_Potemkin();
        }

        return $this->translate;
    }

    public function save(array $newValues, array $filter = null) {
        // Allow to add default fields to any new track
        if ($defaultFields = $this->getDefaultFields()) {
            $keys = $this->getKeys();
            $keys = array_flip($keys);
            $missing = array_diff_key($keys, $newValues);     // On copy track the key exists but is null

            $newValues = parent::save($newValues, $filter);
            if (!empty($missing)) {
                // We have an insert!
                $foundKeys = array_intersect_key($newValues, $missing);
                // Now get the fieldmodel
                $engine     = $this->loader->getTracker()->getTrackEngine($foundKeys['gtr_id_track']);
                $fieldmodel = $engine->getFieldsMaintenanceModel(true, 'create', array());
                $lastOrder  = 0;
                foreach ($defaultFields as $field) {
                    // Load defaults
                    $record = $fieldmodel->loadNew();
                    $record['gtf_id_order'] = $lastOrder + 10;
                    
                    $record = $field + $record;             // Add defaults to the new field
                    $record = $fieldmodel->save($record);
                    $lastOrder = $record['gtf_id_order'];   // Save order for next record
                }
            }
        } else {
            $newValues = parent::save($newValues, $filter);
        }

        return $newValues;
    }
}