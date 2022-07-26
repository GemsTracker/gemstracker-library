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
 * Display only those questions that have an answer
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class CompactFullToggle extends \Gems\Event\SurveyAnswerFilterAbstract
{
     public $IncludeLength = 5;
     public $IncludeStarts = array('score');

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

        $repeater = $model->loadRepeatable();
        $table    = $bridge->getTable();
        $table->setRepeater($repeater);

        // Filter unless option 'fullanswers' is true, can be set as get or post var.
        $requestFullAnswers = \Zend_Controller_Front::getInstance()->getRequest()->getParam('fullanswers', false);
        if (! $repeater->__start()) {
            return $currentNames;
        }

        $keys = array();
        if ($requestFullAnswers !== false) {
            // No filtering
            return $model->getItemsOrdered();

        } else {
            foreach ($model->getItemNames() as $name) {
                $start = substr(strtolower($name),0,$this->IncludeLength);
                if (in_array($start, $this->IncludeStarts)) {
                    $keys[$name] = $name;
                }
            }
        }

        $answers = $this->token->getRawAnswers();
        // Prevent errors when no answers present
        if (!empty($answers)) {
            $results = array_intersect($currentNames, array_keys($keys), array_keys($answers));
        } else {
            $results = array_intersect($currentNames, array_keys($keys));
        }

        $results = $this->restoreHeaderPositions($model, $results);

        if ($results) {
            return $results;
        }

        return $this->getHeaders($model, $currentNames);
    }

    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems\Tracker\Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems\Tracker\Token $token)
    {
        $snippets = parent::getAnswerDisplaySnippets($token);

        array_unshift($snippets, 'Survey\\Display\\FullAnswerToggleSnippet');

        return $snippets;
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->_('Display only the questions whose code starts with `score`.');
    }
}