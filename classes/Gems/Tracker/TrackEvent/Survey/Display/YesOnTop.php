<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event\Survey\Display;

/**
 * Display those questions that are answered with 'yes' op top
 *
 * Questions names that are the same as the yes question but with a longer name
 * separated by an '_' are moved with the question, as are header questions
 * (which may be doubled when not all question come out on top).
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class YesOnTop extends \Gems\Event\SurveyAnswerFilterAbstract
{
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
    public function filterAnswers(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model, array $currentNames)
    {
        if (! $this->token->isCompleted()) {
            return $currentNames;
        }

        $answers  = $this->token->getRawAnswers();
        $onTop    = array();

        // \MUtil\EchoOut\EchoOut::track($answers);

        foreach($answers as $name => $value) {
            if ($value === 'Y') {
                $onTop[$name] = $name;
            } else {
                // Split on last underscore instead of first
                if ($i = strrpos($name, '_')) {
                    if (isset($onTop[substr($name, 0, $i)])) {
                        $onTop[$name] = $name;
                    }
                }
            }
        }

        $currentNames = array_combine($currentNames, $currentNames);
        // \MUtil\EchoOut\EchoOut::track($onTop, $onTop + $currentNames, $currentNames);

        return $this->restoreHeaderPositions($model, $onTop + $currentNames);
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Yes answers on top.');
    }
}
