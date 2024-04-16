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

use Gems\Screens\AskScreenAbstract;
use Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ShowOpenSequenceAsk extends AskScreenAbstract
{
    /**
     * @inheritDoc
     */
    public function getSnippets(Token $token): array
    {
        return ['Gems\\Snippets\\Ask\\ShowOpenSequenceSnippet'];
    }


    /**
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Show token by token with open count.');
    }
}