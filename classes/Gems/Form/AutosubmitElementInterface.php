<?php

/**
 *
 * @package    Gems
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Form;

/**
 * Interface for elements that need to change settings on an autosubmit form.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
interface AutosubmitElementInterface
{
    /**
     * Change the form into an autosubmit form
     *
     * @see \Gems\Form setAutoSubmit
     * @param array $autoSubmitArgs Array containing submitUrl and targetId
     */
    public function enableAutoSubmit(array $autoSubmitArgs);
}
