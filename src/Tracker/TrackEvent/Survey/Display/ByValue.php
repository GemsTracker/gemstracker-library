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
 * Put the highest value first
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class ByValue extends SurveyAnswerFilterAbstract
{
    /**
     * This function is called in addBrowseTableColumns() to filter the names displayed
     * by AnswerModelSnippetGeneric.
     *
     * @see AnswerModelSnippetGeneric
     *
     * @param TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    public function filterAnswers(TableBridge $bridge, ModelAbstract $model, array $currentNames): array
    {
        $currentNames = array_combine($currentNames, $currentNames);
        $newOrder     = array();
        $values       = array_filter($this->token->getRawAnswers(), 'is_numeric');
        arsort($values);

        foreach ($values as $key => $value) {
            if (isset($currentNames[$key])) {
                unset($currentNames[$key]);
                $newOrder[$key] = $key;
            }
        }

        // \MUtil\EchoOut\EchoOut::track($this->_values, $newOrder, $newOrder + $currentNames);

        return $this->restoreHeaderPositions($model, $newOrder + $currentNames);
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName(): string
    {
        return $this->translator->_('Show the highest answer first.');
    }
}
