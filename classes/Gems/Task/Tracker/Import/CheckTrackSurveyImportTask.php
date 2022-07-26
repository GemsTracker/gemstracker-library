<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 19, 2016 12:50:25 PM
 */
class CheckTrackSurveyImportTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Returns tru if another round depends on a round using this export code
     *
     * @param string $exportCode
     * @param array $roundsData
     * @return boolean
     */
    protected function checkRequired($exportCode, array $roundsData)
    {
        // First find rounds using this export code
        $rounds = array();
        foreach ($roundsData as $roundData) {
            if ($roundData['survey_export_code'] == $exportCode) {
                $rounds[$roundData['gro_id_order']] = $roundData['gro_id_order'];
            }
        }

        if (! $rounds) {
            return false;
        }

        // The check for any round using such a round
        foreach ($roundsData as $roundData) {
            if (isset($roundData['gro_valid_after_source'], $roundData['valid_after'])) {
                if ($this->checkSourceRequired(
                        $roundData['gro_valid_after_source'],
                        $roundData['valid_after'],
                        $rounds
                        )) {
                    return true;
                }
            }
            if (isset($roundData['gro_valid_after_source'], $roundData['valid_for'])) {
                if ($this->checkSourceRequired(
                        $roundData['gro_valid_for_source'],
                        $roundData['valid_for'],
                        $rounds
                        )) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns true when thw valid_for_source and valid_for combo uses any round.
     *
     * @param string $source
     * @param string $field
     * @param array $rounds
     * @return boolean
     */
    protected function checkSourceRequired($source, $field, array $rounds)
    {
        if (($source == \Gems\Tracker\Engine\StepEngineAbstract::ANSWER_TABLE) ||
                ($source == \Gems\Tracker\Engine\StepEngineAbstract::TOKEN_TABLE)) {
            return isset($rounds[$field]) && $rounds[$field];
        }
    }

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($lineNr = null, $surveyData = null)
    {
        $batch     = $this->getBatch();
        $import    = $batch->getVariable('import');
        $trackData = $this->util->getTrackData();

        if (isset($surveyData['gsu_export_code']) && $surveyData['gsu_export_code'] && isset($import['rounds'])) {
            $name     = 'survey__' . $surveyData['gsu_export_code'];
            $required = $this->checkRequired($surveyData['gsu_export_code'], $import['rounds']);

            if (isset($surveyData['gsu_survey_name']) && $surveyData['gsu_survey_name']) {
                $import['modelSettings'][$name]['label'] = $surveyData['gsu_survey_name'];
            } else {
                $import['modelSettings'][$name]['label'] = $surveyData['gsu_export_code'];
            }
            $import['modelSettings'][$name]['description'] = sprintf($this->_('[%s]'), $surveyData['gsu_export_code']);
            $import['modelSettings'][$name]['isSurvey']    = true;
            $import['modelSettings'][$name]['required']    = $required;

            $surveyId = $this->db->fetchOne(
                    "SELECT gsu_id_survey FROM gems__surveys WHERE gsu_export_code = ?",
                    $surveyData['gsu_export_code']
                    );

            // The first search determines whether the user can select a survey
            // or the survey is displayed as fixed
            if ($surveyId) {
                $import['modelSettings'][$name]['elementClass'] = 'Exhibitor';
                $import['modelSettings'][$name]['multiOptions'] = $trackData->getAllSurveys();
                $import['surveyCodes'][$surveyData['gsu_export_code']] = $surveyId;
            } else {
                $import['modelSettings'][$name]['elementClass'] = 'Select';

                // Store the exportCode for rows that should be saved
                $import['modelSettings'][$name]['exportCode']  = $surveyData['gsu_export_code'];

                if ($required) {
                    $empty = $this->_('(survey required)');
                } else {
                    $empty = $this->_('(skip rounds)');
                }
                $import['modelSettings'][$name]['multiOptions'] = array('' => $empty) +
                        $trackData->getSurveysWithoutExportCode();
                $import['surveyCodes'][$surveyData['gsu_export_code']] = false;
            }

            // Then try match on gsu_surveyor_id
            if ((! $surveyId) && isset($surveyData['gsu_surveyor_id']) && $surveyData['gsu_surveyor_id']) {
                $surveyId = $this->db->fetchOne(
                        "SELECT gsu_id_survey FROM gems__surveys WHERE gsu_surveyor_id = ?",
                        $surveyData['gsu_surveyor_id']
                        );
            }

            // Last try ny name
            if ((! $surveyId) && isset($surveyData['gsu_survey_name']) && $surveyData['gsu_survey_name']) {
                $surveyId = $this->db->fetchOne(
                        "SELECT gsu_id_survey FROM gems__surveys WHERE gsu_survey_name = ?",
                        $surveyData['gsu_survey_name']
                        );
            }

            // When we have a survey id that is in the options, add it as default
            if ($surveyId) {
                if (isset($import['modelSettings'][$name]['multiOptions'][$surveyId])) {
                    $import['formDefaults'][$name] = $surveyId;
                }
            }
        } else {
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gsu_export_code specified for survey at line %d.'),
                    $lineNr
                    ));
        }
        $batch->setVariable('import', $import);
    }
}
