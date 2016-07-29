<?php

/**
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

            if ($tmpres['type'] === \MUtil_Model::TYPE_DATETIME || $tmpres['type'] === \MUtil_Model::TYPE_DATE || $tmpres['type'] === \MUtil_Model::TYPE_TIME) {
                if ($dateFormats = $this->getDateFormats($name, $tmpres['type'])) {
                    $tmpres = $tmpres + $dateFormats;
                }
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
                    //$parent = '_' . $name . '_';
                    $parent = $field['title'];
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
