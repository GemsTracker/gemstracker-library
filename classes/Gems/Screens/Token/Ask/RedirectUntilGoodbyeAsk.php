<?php

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens\Token\Ask;

use Gems\Screens\AskScreenAbstract;

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 11-Jul-2017 16:52:17
 */
class RedirectUntilGoodbyeAsk extends AskScreenAbstract
{
    /**
     *
     * @param \Gems_Tracker_Token $token
     * @return array Added before all other parameters
     */
    public function getParameters(\Gems_Tracker_Token $token)
    {
        return ['showLastName' => true];
    }

    /**
     *
     * @param \Gems_Tracker_Token $token
     * @return array Of snippets or false to use original
     */
    public function getSnippets(\Gems_Tracker_Token $token)
    {
        return ['Gems\\Snippets\\Ask\\RedirectUntilGoodbyeSnippet'];
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getScreenLabel()
    {
        return $this->_('Answer first open until finished');
    }
}
