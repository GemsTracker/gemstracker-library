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

use Gems\Tracker\Token;
use Gems\Tracker\TrackEvent\SurveyAnswerFilterAbstract;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;

/**
 * Display only those questions that have an answer
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class CompactFullToggle extends SurveyAnswerFilterAbstract
{
     public int $includeLength = 5;

     public array $includeStarts = ['score'];

    /**
     * This function is called in addBrowseTableColumns() to filter the names displayed
     * by AnswerModelSnippetGeneric.
     *
     * @see \Gems\Tracker\Snippets\AnswerModelSnippetGeneric
     *
     * @param TableBridge $bridge
     * @param DataReaderInterface $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    public function filterAnswers(TableBridge $bridge, DataReaderInterface $model, array $currentNames): array
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
                $start = substr(strtolower($name),0,$this->includeLength);
                if (in_array($start, $this->includeStarts)) {
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
     * @param Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(Token $token): array
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
    public function getEventName(): string
    {
        return $this->translator->_('Display only the questions whose code starts with `score`.');
    }
}