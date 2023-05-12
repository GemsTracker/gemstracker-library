<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Token\Ask;

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ShowFirstOpenAskPrivate extends ShowFirstOpenAsk
{
    /**
     *
     * @param \Gems\Tracker\Token $token
     * @return array Added before all other parameters
     */
    public function getParameters(\Gems\Tracker\Token $token)
    {
        return ['showLastName' => false];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('Show first open token only - without lastname');
    }
}