<?php

/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Event;

/**
 * Survey display event interface.
 *
 * Just a snippet with extra event code.
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
interface SurveyDisplayEventInterface extends \Gems\Event\EventInterface
{
    /**
     * Function that returns the snippets to use for this display.
     *
     * @param \Gems\Tracker\Token $token The token to get the snippets for
     * @return array of Snippet names or nothing
     */
    public function getAnswerDisplaySnippets(\Gems\Tracker\Token $token);
}
