<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Put the last question first
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Gems_Event_Survey_Display_Reverse extends \Gems_Event_SurveyAnswerFilterAbstract
{
    /**
     * This function is called in addBrowseTableColumns() to filter the names displayed
     * by AnswerModelSnippetGeneric.
     *
     * @see \Gems_Tracker_Snippets_AnswerModelSnippetGeneric
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    public function filterAnswers(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model, array $currentNames)
    {
        return $this->restoreHeaderPositions($model, array_reverse($currentNames));
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Reverse the question order.');
    }
}
