<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Util\Translated;

/**
 * Simple stub for track model, allows extension by projects and adds auto labelling
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class TrackModel extends \MUtil\Model\TableModel
{
    /**
     * Holds the trackData in array with key trackId, for internal caching use only
     *
     * @var array
     */
    protected $_trackData = array();

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems\Tracker
     */
    protected $tracker;

    /**
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Construct a track model
     */
    public function __construct()
    {
        parent::__construct('gems__tracks');

        $this->addColumn("CASE WHEN gtr_track_class = 'SingleSurveyEngine' THEN 'deleted' ELSE '' END", 'row_class');

        \Gems\Model::setChangeFieldsByPrefix($this, 'gtr');

        $this->set('gtr_date_start', 'default', new \DateTimeImmutable());
        $this->setKeys(['trackId' => 'gtr_id_track']);
    }

    /**
     * Sets the labels, format functions, etc...
     *
     * @param boolean $detailed True when shopwing detailed information
     * @param boolean $edit When true use edit settings
     * @return \Gems\Tracker\Model\TrackModel
     */
    public function applyFormatting($detailed = false, $edit = false)
    {
        $translator = $this->getTranslateAdapter();

        $this->resetOrder();

        $this->set('gtr_track_name',
            'label', $translator->_('Name'),
            'translate', true
        );
        $this->set('gtr_external_description', 
                   'label', $translator->_('External Name'),
                   'description', $translator->_('Optional alternate external description for communication with respondents'),
                   'translate', true
        );
        $this->set('gtr_track_class',   'label', $translator->_('Track Engine'),
                'multiOptions', $this->tracker->getTrackEngineList($detailed));
        $this->set('gtr_survey_rounds', 'label', $translator->_('Surveys'));

        $this->set('gtr_active',        'label', $translator->_('Active'),
                'multiOptions', $this->translatedUtil->getYesNo());
        $this->set('gtr_date_start',    'label', $translator->_('From'),
                'formatFunction', $this->translatedUtil->formatDate);
        $this->set('gtr_date_until',    'label', $translator->_('Use until'),
                'formatFunction', $this->translatedUtil->formatDateForever);
        $this->setIfExists('gtr_code',  'label', $translator->_('Track code'),
                'size', 10,
                'description', $translator->_('Optional code name to link the track to program code.'));

        $this->loader->getModels()->addDatabaseTranslationEditFields($this);

        if ($detailed) {
            $events = $this->loader->getEvents();

            $caList = $events->listTrackCalculationEvents();
            if (count($caList) > 1) {
                $this->setIfExists('gtr_calculation_event', 'label', $translator->_('Before (re)calculation'),
                        'multiOptions', $caList
                        );
            }

            $coList = $events->listTrackCompletionEvents();
            if (count($coList) > 1) {
                $this->setIfExists('gtr_completed_event', 'label', $translator->_('After completion'),
                        'multiOptions', $coList
                        );
            }

            $bfuList = $events->listTrackBeforeFieldUpdateEvents();
            if (count($bfuList) > 1) {
                $this->setIfExists('gtr_beforefieldupdate_event', 'label', $translator->_('Before field update'),
                        'multiOptions', $bfuList
                        );
            }

            $fuList = $events->listTrackFieldUpdateEvents();
            if (count($fuList) > 1) {
                $this->setIfExists('gtr_fieldupdate_event', 'label', $translator->_('After field update'),
                        'multiOptions', $fuList
                        );
            }
            $this->setIfExists('gtr_organizations', 'label', $translator->_('Organizations'),
                    'elementClass', 'MultiCheckbox',
                    'multiOptions', $this->util->getDbLookup()->getOrganizationsWithRespondents(),
                    'required', true
                    );
            $ct = new \MUtil\Model\Type\ConcatenatedRow('|', $translator->_(', '));
            $ct->apply($this, 'gtr_organizations');
        }
        if ($edit) {
            $this->set('toggleOrg',
                    'elementClass', 'ToggleCheckboxes',
                    'selectorName', 'gtr_organizations'
                    );
            $this->set('gtr_track_name',
                    'minlength', 4,
                    'size', 30,
                    'validators[unique]', $this->createUniqueValidator('gtr_track_name')
                    );
        }
        if ($this->project->translateDatabaseFields()) {
            if ($edit) {
                $this->loader->getModels()->addDatabaseTranslationEditFields($this);
            } else {
                $this->loader->getModels()->addDatabaseTranslations($this);
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
     * Delete items from the model
     *
     * This method also takes care of cascading to track fields and rounds
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        $this->setChanged(0);
        $tracks = $this->load($filter);

        if ($tracks) {
            foreach ($tracks as $row) {
                if (isset($row['gtr_id_track'])) {
                    $trackId = $row['gtr_id_track'];
                    if ($this->isDeleteable($trackId)) {
                        $this->db->delete('gems__tracks', $this->db->quoteInto('gtr_id_track = ?', $trackId));

                        // Now cascade to children, they should take care of further cascading
                        // Delete rounds
                        $trackEngine = $this->tracker->getTrackEngine($trackId);
                        $roundModel  = $trackEngine->getRoundModel(true, 'index');
                        $roundModel->delete(['gro_id_track' => $trackId]);

                        // Delete trackfields
                        $trackFieldModel = $trackEngine->getFieldsMaintenanceModel(false, 'index');
                        $trackFieldModel->delete(['gtf_id_track' => $trackId]);

                        // Delete assigned but unused tracks
                        $this->db->delete('gems__respondent2track',  $this->db->quoteInto('gr2t_id_track = ?', $trackId));
                    } else {
                        $values['gtr_id_track'] = $trackId;
                        $values['gtr_active']   = 0;
                        $this->save($values);
                    }
                    $this->addChanged();
                }
            }
        }

        return $this->getChanged();
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
        return $defaultFields;
        */

        return [];
    }

    /**
     * Get the number of times someone started answering a round in this track.
     *
     * @param int $trackId
     * @return int
     */
    public function getStartCount($trackId)
    {
        if (! $trackId) {
            return 0;
        }

        $sql = "SELECT COUNT(DISTINCT gto_id_respondent_track) FROM gems__tokens WHERE gto_id_track = ? AND gto_start_time IS NOT NULL";
        return $this->db->fetchOne($sql, $trackId);
    }

    /**
     * Returns a translate adaptor
     *
     * @return \Zend_Translate_Adapter
     */
    protected function getTranslateAdapter()
    {
        if ($this->translate instanceof \Zend_Translate)
        {
            return $this->translate->getAdapter();
        }

        if (! $this->translate instanceof \Zend_Translate_Adapter) {
            $this->translate = new \MUtil\Translate\Adapter\Potemkin();
        }

        return $this->translate;
    }

    /**
     * Can this track be deleted as is?
     *
     * @param int $trackId
     * @return boolean
     */
    public function isDeleteable($trackId)
    {
        if (! $trackId) {
            return true;
        }
        $sql = "SELECT gto_id_token FROM gems__tokens WHERE gto_id_track = ? AND gto_start_time IS NOT NULL";
        return (boolean) ! $this->db->fetchOne($sql, $trackId);
    }

    public function save(array $newValues, array $filter = null)
    {
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
                $fieldmodel = $engine->getFieldsMaintenanceModel(true, 'create');
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
