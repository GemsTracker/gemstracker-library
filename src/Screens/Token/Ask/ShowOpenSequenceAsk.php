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
class ShowOpenSequenceAsk extends \Gems\Screens\AskScreenAbstract
{
    /**
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of snippets or false to use original
     */
    public function getSnippets(\Gems\Tracker\Token $token)
    {
        return ['Gems\\Snippets\\Ask\\ShowOpenSequenceSnippet'];
    }


    /**
     * @inheritDoc
     */
    public function getScreenLabel()
    {
        return $this->_('Show token by token with open count.');
    }
}