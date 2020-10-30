<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model;

use Gems\Tracker\Engine\FieldsDefinition;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 13-feb-2014 16:33:25
 */
class AddTrackFieldsTransformer extends \MUtil_Model_ModelTransformerAbstract
{
    /**
     *
     * @var \Gems\Tracker\Engine\FieldsDefinition;
     */
    protected $fieldsDefinition;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var string The field that contains the respondent track id
     */
    protected $respTrackIdField = 'gr2t_id_respondent_track';

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @param \Gems_Loader; $loader
     * @param \Gems\Tracker\Engine\FieldsDefinition; $fieldsDefinition
     * @param mixed $respTrackIdField Overwrite the default field that contains the respondent track id (gr2t_id_respondent_track)
     */
    public function __construct(\Gems_Loader $loader, FieldsDefinition $fieldsDefinition, $respTrackIdField = false)
    {
        $this->loader = $loader;
        $this->fieldsDefinition = $fieldsDefinition;
        if ($respTrackIdField) {
            $this->respTrackIdField = $respTrackIdField;
        }
    }

    /**
     * Get default values of empty fields
     *
     * @return array
     */
    public function getEmptyFieldsData()
    {
        return $this->fieldsDefinition->getFieldDefaults();
    }

    /**
     * If the transformer adds fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @return array Of field name => set() values
     */
    public function getFieldInfo(\MUtil_Model_ModelAbstract $model)
    {
        // Many definitions use load transformers
        $model->setMeta(\MUtil_Model_ModelAbstract::LOAD_TRANSFORMER, true);

        $settings = $this->fieldsDefinition->getDataModelSettings();
        foreach ($settings as $field => &$setting) {
            $setting['noSort'] = true;
            $setting['no_text_search'] = true;
        }
        
        return $settings;
    }

    /**
     * This transform function checks the filter for
     * a) retrieving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     * /
    public function transformFilter(\MUtil_Model_ModelAbstract $model, array $filter)
    {
        $textSearch = isset($filter[$model->getTextFilter()]) ? $filter[$model->getTextFilter()] : false;
        $fieldModel = $this->fieldsDefinition->getDataStorageModel();
        
        if ($textSearch && $fieldModel instanceof \MUtil_Model_UnionModel) {
            foreach ($this->fieldsDefinition->getFieldNames() as $key => $label) {
                $defs = FieldsDefinition::splitKey($key);

                $subFilter = [
                    'sub' => $defs['sub'],
                    'gr2t2f_id_field' => $defs['gtf_id_field'],
                    'gr2t2f_value' => $textSearch,
                    ];

                // SHIT! Only filters on key values!               
                \MUtil_Echo::track($fieldModel->load($subFilter));
            }
        }
        // \MUtil_Echo::track($model->getTextFilter(), $this->fieldsDefinition->getFieldNames(), array_keys($this->fieldsDefinition->getDataStorageModel()->getUnionModels()), $filter);
        
        // No changes
        return $filter;
    }
    
    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(\MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        if ($isPostData) {
            return $data;
        }
        
        $empty = false;

        foreach ($data as $key => $row) {

            if (isset($row[$this->respTrackIdField]) && $row[$this->respTrackIdField]) {
                $fields = $this->fieldsDefinition->getFieldsDataFor($row[$this->respTrackIdField]);
            } else {

                if (! $empty) {
                    $empty = $this->getEmptyFieldsData();
                }
                $fields = $empty;
            }

            //$data[$key] = array_merge($row, $fields);
            $data[$key] = array_replace($row, $fields);
            //$data[$key] = $row  $fields;
        }

        return $data;
    }

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row[$this->respTrackIdField]) && $row[$this->respTrackIdField]) {
            if (! $this->tracker) {
                $this->tracker = $this->loader->getTracker();
            }

            if ((! $this->respTrackIdField) || ($this->respTrackIdField == 'gr2t_id_respondent_track')) {
                // Load && refresh when using standard gems__respondent2track data
                $respTrack = $this->tracker->getRespondentTrack($row);
            } else {
                $respTrack = $this->tracker->getRespondentTrack($row[$this->respTrackIdField]);
            }

            // We use setFieldDate instead of saveFields() since it handles updating related values
            // like dates derived from appointments
            $before = $respTrack->getFieldData();       // Get old so we can detect changes
            $after  = $respTrack->setFieldData($row);
            $row = $after + $row;
            $changed = ($before !== $after);

            if ($changed && (! $model->getChanged())) {
                $model->addChanged(1);
            }
        }

        // No changes
        return $row;
    }

    /**
     * This transform function is called before the saving of the data in the source model and allows you to
     * change all data.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     * /
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        if (! $this->tracker) {
            $this->tracker = $this->loader->getTracker();
        }

        if (isset($row[$this->respTrackIdField]) && $row[$this->respTrackIdField]) {
            $respTrack = $this->tracker->getRespondentTrack($row[$this->respTrackIdField]);
            $fields    = $respTrack->processFieldsBeforeSave($row);

            $row['gr2t_track_info'] = $this->fieldsDefinition->calculateFieldsInfo($fields);

            // Also save the calculated fields into the row (actual save is in transformRowAfterSave)
            return $fields + $row;
        }

        return $row;
    }

    /**
     * When true, the on save functions are triggered before passing the data on
     *
     * @return boolean
     * /
    public function triggerOnSaves()
    {
        return true;
    }
     */
}