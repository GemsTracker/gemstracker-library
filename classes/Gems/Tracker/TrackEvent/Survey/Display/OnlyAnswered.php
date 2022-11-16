<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\TrackEvent\Survey\Display;

use Gems\Tracker\TrackEvent\SurveyAnswerFilterAbstract;
use MUtil\Model\Bridge\TableBridge;
use MUtil\Model\ModelAbstract;

/**
 * Display only those questions that have an answer
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class OnlyAnswered extends SurveyAnswerFilterAbstract
{

    /**
     * Strip elements from the array that are considered empty
     *
     * Empty is NULL or empty string, values of 0 are NOT empty unless they are a checkbox
     *
     * @param array $inputArray
     * @param \MUtil\Model\ModelAbstract $model
     * @return boolean
     */
    public function array_filter(array $inputArray, ModelAbstract $model): array
    {
        $outputArray = array();
        foreach ($inputArray as $key => $value) {
            // Null and empty string are skipped
            if (is_null($value) || $value === '') {
                continue;
            }
            // Maybe do a check on multiOptions for checkboxes etc. to disable some 0 values $model->get($key, 'multiOptions');
            if ($value == '0' && $options = $model->get($key, 'multiOptions')) {
                if (count($options) == 2) {
                    // Probably a checkbox (multi flexi in limesurvey)
                    continue;
                }
            }
            $outputArray[$key] = $value;
        }
        return $outputArray;
    }

    /**
     * This function is called in addBrowseTableColumns() to filter the names displayed
     * by AnswerModelSnippetGeneric.
     *
     * @see \Gems\Tracker\Snippets\AnswerModelSnippetGeneric
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    public function filterAnswers(TableBridge $bridge, ModelAbstract $model, array $currentNames): array
    {
        $rows = $bridge->getRows();
        if (! $rows) {
            return $currentNames;
        }

        $keys = array();
        foreach ($rows as $row) {
            // Add the keys that contain values.
            $keys += $this->array_filter($row, $model);
        }

        $results = array_intersect($currentNames, array_keys($keys), array_keys($this->token->getRawAnswers()));
        // \MUtil\EchoOut\EchoOut::track($results);

        $results = $this->restoreHeaderPositions($model, $results);

        if ($results) {
            return $results;
        }

        return $this->getHeaders($model, $currentNames);
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Display only the questions with an answer.');
    }
}