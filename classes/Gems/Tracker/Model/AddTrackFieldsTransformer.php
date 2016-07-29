<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
     * @var \Gems_loader
     */
    protected $loader;

    /**
     *
     * @var string The field that contains the respondent track id
     */
    protected $respTrackIdField = 'gr2t_id_respondent_track';

    /**
     *
     * @param \Gems_Loader; $loader
     * @param \Gems\Tracker\Engine\FieldsDefinition; $fieldsDefinition
     * @param $respTrackIdField Overwrite the default field that contains the respondent track id (gr2t_id_respondent_track)
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
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(\MUtil_Model_ModelAbstract $model)
    {
        // Many definitions use load transformers
        $model->setMeta(\MUtil_Model_ModelAbstract::LOAD_TRANSFORMER, true);

        return $this->fieldsDefinition->getDataModelSettings();
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
                    $empty = array_fill_keys(array_keys($this->fieldsDefinition->getFieldNames()), null);
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
            // Field data was already (re)calculated in transformRowBeforeSave
            // and saveFields() extracts the used field data from the row.
            $changed = $this->fieldsDefinition->saveFields($row[$this->respTrackIdField], $row);

            if ($changed) {
                $tracker   = $this->loader->getTracker();
                $respTrack = $tracker->getRespondentTrack($row[$this->respTrackIdField]);
                $userId    = $this->loader->getCurrentUser()->getUserId();

                $respTrack->handleFieldUpdate($userId);

                if (! $model->getChanged()) {
                    $model->addChanged(1);
                }
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
     */
    public function transformRowBeforeSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $fields = $this->fieldsDefinition->processBeforeSave($row, $row);
        $row['gr2t_track_info'] = $this->fieldsDefinition->calculateFieldsInfo($fields);

        // Also save the calculated fields into the row (actual save is in transformRowAfterSave)
        $row = $fields + $row;

        return $row;
    }

    /**
     * When true, the on save functions are triggered before passing the data on
     *
     * @return boolean
     */
    public function triggerOnSaves()
    {
        return true;
    }
}
