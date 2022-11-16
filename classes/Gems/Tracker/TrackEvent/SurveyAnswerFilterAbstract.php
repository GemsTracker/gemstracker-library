<?php

/**
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event;

/**
 * Abstract class for defining filters on answer displays
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
abstract class SurveyAnswerFilterAbstract extends \MUtil\Translate\TranslateableAbstract
    implements \Gems\Event\SurveyDisplayEventInterface, \Gems\Tracker\Snippets\AnswerNameFilterInterface
{
    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems\Tracker\Token
     */
    protected $token;

    // public function filterAnswers(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model, array $currentNames);

    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems\Tracker\Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems\Tracker\Token $token)
    {
        $this->token = $token;

        $snippets = (array) $token->getTrackEngine()->getAnswerSnippetNames();

        $snippets['answerFilter'] = $this;

        return $snippets;
    }

    // public function getEventName()

    /**
     * Returns only the headers
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    protected function getHeaders(\MUtil\Model\ModelAbstract $model, array $currentNames)
    {
        $lastParent = null;
        $results    = array();
        foreach ($currentNames as $name) {
            if ($model->is($name, 'type', \MUtil\Model::TYPE_NOVALUE)) {
                $results[$name] = $name;

            } elseif ($parent = $model->get($name, 'parent_question')) {
                // Insert parent header on name if it was not shown before
                $results[$parent] = $parent;
            }
        }

        return $results;
    }

    /**
     * Restores the header position of question before their corresponding question_sub
     *
     * When sub-questions with the same parent are shown continuous the parent is shown
     * once before them. When the sub-questions are displayed in seperate groups the
     * parent is shown once at their start.
     *
     * Stand alone headers without any corresponding value are removed. When they do have
     * a value of their own they are still shown, but their position may change according
     * to their sub-questions position. (NOTE: As in LimeSurvey their are no question
     * headers with values we leave it at this for the moment.)
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    protected function restoreHeaderPositions(\MUtil\Model\ModelAbstract $model, array $currentNames)
    {
        $lastParent = null;
        $results    = array();
        foreach ($currentNames as $name) {
            if ($model->is($name, 'type', \MUtil\Model::TYPE_NOVALUE)) {
                // Skip header types that contain no value
                continue;
            }

            if ($parent = $model->get($name, 'parent_question')) {
                // Check for change of parent
                if ($lastParent !== $parent) {
                    if (isset($results[$parent])) {
                        // Add another copy of the parent to the array
                        $results[] = $parent;
                    } else {
                        // Insert parent header on name if it was not shown before
                        $results[$parent] = $parent;
                    }
                    $lastParent = $parent;
                }
            } else {
                // Make sure a question (without parent) is picked up as parent too
                $lastParent = $name;
            }

            // If already set (as a $parent) this will not
            // redisplay the $parent as $result[$name] does
            // not change position
            $results[$name] = $name;
        }

        return $results;
    }
}
