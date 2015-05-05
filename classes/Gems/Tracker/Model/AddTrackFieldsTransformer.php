<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
