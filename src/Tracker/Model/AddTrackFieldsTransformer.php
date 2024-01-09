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

use Gems\Tracker;
use Gems\Tracker\Engine\FieldsDefinition;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 13-feb-2014 16:33:25
 */
class AddTrackFieldsTransformer extends ModelTransformerAbstract
{
    /**
     *
     * @var \Gems\Tracker\Engine\FieldsDefinition;
     */
    protected $fieldsDefinition;

    /**
     *
     * @var string The field that contains the respondent track id
     */
    protected ?string $respTrackIdField = 'gr2t_id_respondent_track';

    /**
     *
     * @param string|null $respTrackIdField Overwrite the default field that contains the respondent track id (gr2t_id_respondent_track)
     */
    public function __construct(
        protected Tracker $tracker,
        FieldsDefinition $fieldsDefinition,
        ?string $respTrackIdField = null
    ) {
        $this->fieldsDefinition = $fieldsDefinition;
        if ($respTrackIdField !== null) {
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
     * @param MetaModelInterface $model The parent model
     * @return array Of field name => set() values
     */
    public function getFieldInfo(MetaModelInterface $model): array
    {
        // Many definitions use load transformers
        $model->setMeta(MetaModel::LOAD_TRANSFORMER, true);

        $settings = $this->fieldsDefinition->getDataModelSettings();
        foreach ($settings as $field => &$setting) {
            $setting['noSort'] = true;
            $setting['no_text_search'] = true;
            $setting[SqlRunnerInterface::NO_SQL] = true;

            if (isset($setting[MetaModel::SAVE_TRANSFORMER])) {
                $model->setMeta(MetaModel::SAVE_TRANSFORMER, true);
            }
        }

        return $settings;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MetaModelInterface $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        if ($isPostData) {
            return $data;
        }

        $empty = false;

        foreach ($data as $key => $row) {
            if (isset($row[$this->respTrackIdField]) && $row[$this->respTrackIdField]) {
                $fields = $this->fieldsDefinition->getFieldsDataFor($row[$this->respTrackIdField]);
            } else {
                if (!$empty) {
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
     * @param MetaModelInterface $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MetaModelInterface $model, array $row)
    {
        if (isset($row[$this->respTrackIdField]) && $row[$this->respTrackIdField]) {
            if ((!$this->respTrackIdField) || ($this->respTrackIdField == 'gr2t_id_respondent_track')) {
                // Load && refresh when using standard gems__respondent2track data
                $respTrack = $this->tracker->getRespondentTrack($row);
            } else {
                $respTrack = $this->tracker->getRespondentTrack($row[$this->respTrackIdField]);
            }

            // We use setFieldData instead of saveFields() since it handles updating related values
            // like dates derived from appointments
            // TODO(Koen) add tracking of changes back in? If so how?
//            $before = $respTrack->getFieldData();       // Get old so we can detect changes
            $after = $respTrack->setFieldData($row);
            $row = $after + $row;
//            $changed = ($before !== $after);

//            if ($changed && (! $model->getChanged())) {
//                $model->addChanged(1);
//            }
        }

        // No changes
        return $row;
    }
}