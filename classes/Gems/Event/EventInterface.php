<?php
/**
 *
 * @package    Gems
 * @subpackage Event
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * To be able to display lists of possible event classes in a list and to mark events as such,
 * each event should implement this interface.
 *
 * @package    Gems
 * @subpackage Event
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
interface Gems_Event_EventInterface
{
    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName();
}
