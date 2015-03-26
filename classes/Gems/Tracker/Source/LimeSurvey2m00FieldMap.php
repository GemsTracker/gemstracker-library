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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LimeSurvey1m9FieldMap.php 2041 2014-07-23 16:17:51Z matijsdejong $
 */

/**
 * A fieldmap object adds LS source code knowledge and interpretation to the database data
 * about a survey. This enables the code to work with the survey object.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Tracker_Source_LimeSurvey2m00FieldMap extends \Gems_Tracker_Source_LimeSurvey1m9FieldMap
{
    /**
     * Applies the fieldmap data to the model
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    public function applyToModel(\MUtil_Model_ModelAbstract $model)
    {
        $map    = $this->_getMap();
        $oldfld = null;
        $parent = null;

        foreach ($map as $name => $field) {

            $tmpres = array();
            $tmpres['thClass']         = \Gems_Tracker_SurveyModel::CLASS_MAIN_QUESTION;
            $tmpres['group']           = $field['gid'];
            $tmpres['type']            = $this->_getType($field);
            $tmpres['survey_question'] = true;

            if ($tmpres['type'] === \MUtil_Model::TYPE_DATE) {
                $tmpres['storageFormat'] = 'yyyy-MM-dd';
                $tmpres['dateFormat']    = 'dd MMMM yyyy';
                // $tmpres['formatFunction']
            }

            if ($tmpres['type'] === \MUtil_Model::TYPE_DATETIME) {
                $tmpres['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                $tmpres['dateFormat']    = 'dd MMMM yyyy HH:mm';
                // $tmpres['formatFunction']
            }

            if ($tmpres['type'] === \MUtil_Model::TYPE_TIME) {
                $tmpres['storageFormat'] = 'yyyy-MM-dd HH:mm:ss';
                $tmpres['dateFormat']    = 'HH:mm:ss';
                // $tmpres['formatFunction']
            }

            if ($tmpres['type'] === \MUtil_Model::TYPE_NUMERIC) {
                $tmpres['formatFunction'] = array($this, 'handleFloat');
            }

            $oldQuestion = isset($oldfld['question']) ? $oldfld['question'] : null;
            if (isset($field['question']) && (! isset($oldfld) || $oldQuestion !== $field['question'])) {
                $tmpres['label'] = \MUtil_Html::raw($this->removeMarkup($field['question']));
            }
            if (isset($field['help']) && $field['help']) {
                $tmpres['description'] = \MUtil_Html::raw($this->removeMarkup($field['help']));
            }

            // Juggle the labels for sub-questions etc..
            if (isset($field['sq_question'])) {
                if (isset($tmpres['label'])) {
                    // Add non answered question for grouping and make it the current parent
                    $parent = '_' . $name . '_';
                    $model->set($parent, $tmpres);
                    $model->set($parent, 'type', \MUtil_Model::TYPE_NOVALUE);
                }
                if (isset($field['sq_question1'])) {
                    $tmpres['label'] = \MUtil_Html::raw(sprintf(
                            $this->translate->_('%s: %s'),
                            $this->removeMarkup($field['sq_question']),
                            $this->removeMarkup($field['sq_question1'])
                            ));
                } else {
                    $tmpres['label'] = \MUtil_Html::raw($this->removeMarkup($field['sq_question']));
                }
                $tmpres['thClass'] = \Gems_Tracker_SurveyModel::CLASS_SUB_QUESTION;
            }
            if ($options = $this->_getMultiOptions($field)) {
                $tmpres['multiOptions'] = $options;
            }
            // Code does not have to be unique. So if a title is used
            // twice we only use it for the first result.
            if (isset($field['code']) && (! $model->has($field['code']))) {
                $name = $field['code'];
            }

            // Parent storage
            if (\Gems_Tracker_SurveyModel::CLASS_MAIN_QUESTION === $tmpres['thClass']) {
                $parent = $name;
            } elseif ($parent) {
                // Add the name of the parent item
                $tmpres['parent_question'] = $parent;
            }

            $model->set($name, $tmpres);

            $oldfld = $field;
        }
    }


    /**
     * Function to cast numbers as float, but leave null intact
     * @param  The number to cast to float
     * @return float
     */
    public function handleFloat($value)
    {
        return is_null($value) ? null : (float)$value;
    }
}
