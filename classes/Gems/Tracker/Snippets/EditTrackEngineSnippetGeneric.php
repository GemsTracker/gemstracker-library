<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Basic snippet for editing track engines instances
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Snippets_EditTrackEngineSnippetGeneric extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var string Field for storing the old track class
     */
    protected $_oldClassName = 'old__gtr_track_class';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Optional, required when creating or $trackId should be set
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * Optional, required when creating or $engine should be set
     *
     * @var int Track Id
     */
    protected $trackId;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if (! $this->createData) {
            $bridge->addHidden('gtr_id_track');
            $bridge->addHidden('table_keys');
        }
        $bridge->addText('gtr_track_name');
        if ($this->project->translateDatabaseFields()) {
            $bridge->addFormTable('translations_gtr_track_name');
        }
        $bridge->addText('gtr_external_description');
        if ($this->project->translateDatabaseFields()) {
            $bridge->addFormTable('translations_gtr_external_description');
        }


        // gtr_track_class
        if ($this->trackEngine) {
            $options      = $model->get('gtr_track_class', 'multiOptions');
            $alternatives = $this->trackEngine->getConversionTargets($options);
            if (count($alternatives) > 1) {
                $options = $alternatives;

                $bridge->addHidden($this->_oldClassName);

                if (! isset($this->formData[$this->_oldClassName])) {
                    $this->formData[$this->_oldClassName] = $this->formData['gtr_track_class'];
                }

                $classEdit = true;
            } else {
                $classEdit = false;
            }
        } else {
            $tracker = $this->loader->getTracker();
            $options = $tracker->getTrackEngineList(true, true);
            $classEdit = true;
        }
        $model->set('gtr_track_class', 'multiOptions', $options, 'escape', false);
        if ($classEdit) {
            $bridge->addRadio(    'gtr_track_class');
        } else {
            $bridge->addExhibitor('gtr_track_class');
        }

        $bridge->addDate('gtr_date_start');
        $bridge->addDate('gtr_date_until');
        //if (! $this->createData) {
            $bridge->addCheckbox('gtr_active');
        //}
        if ($model->has('gtr_code')) {
            $bridge->addText('gtr_code');
        }
        if ($model->has('gtr_calculation_event', 'label')) {
            $bridge->add('gtr_calculation_event');
        }
        if ($model->has('gtr_completed_event', 'label')) {
            $bridge->add('gtr_completed_event');
        }
        if ($model->has('gtr_beforefieldupdate_event', 'label')) {
            $bridge->add('gtr_beforefieldupdate_event');
        }
        if ($model->has('gtr_fieldupdate_event', 'label')) {
            $bridge->add('gtr_fieldupdate_event');
        }
        $bridge->add('gtr_organizations');

        $element = new \MUtil_Bootstrap_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=gtr_organizations]'));

        $element->setLabel($this->_('Toggle'));
        $bridge->addElement($element);
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->db && $this->loader && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->loader->getTracker()->getTrackModel();
        $model->applyFormatting(true, true);

        return $model;
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     * /
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->addByController('track', 'show-track', $this->_('Show track'))
                ->addByController('track', 'index', $this->_('Show tracks'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    } // */

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('track', 'tracks', $count);
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        if ($this->createData) {
            return $this->_('Add new track');
        } else {
            return parent::getTitle();
        }
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->trackEngine && (! $this->trackId)) {
            $this->trackId = $this->trackEngine->getTrackId();
        }

        if ($this->trackId) {
            // We are updating
            $this->createData = false;

            // Try to get $this->trackEngine filled
            if (! $this->trackEngine) {
                // Set the engine used
                $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->trackId);
            }

        } else {
            // We are inserting
            $this->createData = true;
            $this->saveLabel = $this->_($this->_('Add new track'));
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        // feature request #200
        if (isset($this->formData['gtr_organizations']) && (! is_array($this->formData['gtr_organizations']))) {
            $this->formData['gtr_organizations'] = explode('|', trim($this->formData['gtr_organizations'], '|'));
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        // feature request #200
        if (isset($this->formData['gtr_organizations']) && is_array($this->formData['gtr_organizations'])) {
            $this->formData['gtr_organizations'] = '|' . implode('|', $this->formData['gtr_organizations']) . '|';
        }
        if ($this->trackEngine) {
            $this->formData['gtr_survey_rounds'] = $this->trackEngine->calculateRoundCount();
        } else {
            $this->formData['gtr_survey_rounds'] = 0;
        }

        parent::saveData();

        // Check for creation
        if ($this->createData) {
            if (isset($this->formData['gtr_id_track'])) {
                $this->trackId = $this->formData['gtr_id_track'];
            }
        } elseif ($this->trackEngine &&
                isset($this->formData[$this->_oldClassName], $this->formData['gtr_track_class']) &&
                $this->formData[$this->_oldClassName] != $this->formData['gtr_track_class']) {

            // Track conversion
            $this->trackEngine->convertTo($this->formData['gtr_track_class']);
        }
    }
}
