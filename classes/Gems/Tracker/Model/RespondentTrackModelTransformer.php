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
 * @subpackage RespondentTrackModelTransformer
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentTrackModelTransformer.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage RespondentTrackModelTransformer
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 13-feb-2014 16:33:25
 */
class Gems_Tracker_Model_RespondentTrackModelTransformer extends MUtil_Model_ModelTransformerAbstract
{
    /**
     *
     * @var Gems_Tracker_Engine_FieldsDefinition
     */
    protected $fieldsDefinition;

    /**
     *
     * @param \Gems_Tracker_Engine_FieldsDefinition $fieldsDefinition
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     */
    public function __construct(\Gems_Tracker_Engine_FieldsDefinition $fieldsDefinition, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $this->fieldsDefinition = $fieldsDefinition;

        $this->_fields = $fieldsDefinition->getDataEditModelSettings($respondentId, $organizationId, $patientNr, $edit);
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MUtil_Model_ModelAbstract $model, array $data, $new = false, $isPostData = false)
    {
        $empty = false;

        foreach ($data as $key => $row) {
            if (isset($row['gr2t_id_respondent_track']) && $row['gr2t_id_respondent_track']) {
                $fields = $this->fieldsDefinition->getFieldsDataFor($row['gr2t_id_respondent_track']);
            } else {
                if (! $empty) {
                    $empty = array_fill_keys(array_keys($this->fieldsDefinition->getFieldNames()), null);
                }
                $fields = $empty;
            }

            $data[$key] = $row + $fields;
        }

        return $data;
    }

    /**
     * This transform function performs the actual save of the data and is called after
     * the saving of the data in the source model.
     *
     * @param MUtil_Model_ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MUtil_Model_ModelAbstract $model, array $row)
    {
        if (isset($row['gr2t_id_respondent_track']) && $row['gr2t_id_respondent_track']) {
            $changed = $this->fieldsDefinition->setFieldsData($row['gr2t_id_respondent_track'], $row);

            if ($changed && (!$model->getChanged())) {
                $model->addChanged(1);
            }
        }

        // No changes
        return $row;
    }
}
