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
 * Survey completion event interface.
 *
 * Just return those values that have changed and they will be uploaded to the source.
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
interface SurveyCompletedEventInterface extends \Gems\Event\EventInterface
{
    /**
     * Process the data and return the answers that should be changed.
     *
     * Storing the changed values is handled by the calling function.
     *
     * @param \Gems\Tracker\Token $token \Gems token object
     * @return array Containing the changed values
     */
    public function processTokenData(\Gems\Tracker\Token $token);
}
