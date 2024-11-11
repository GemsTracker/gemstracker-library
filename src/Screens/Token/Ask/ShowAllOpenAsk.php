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
use Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 11-Jul-2017 16:52:17
 */
class ShowAllOpenAsk extends AskScreenAbstract
{
    /**
     * @inheritDoc
     */
    public function getParameters(Token $token): array
    {
        return ['showLastName' => true];
    }

    /**
     * @inheritDoc
     */
    public function getSnippets(Token $token): array
    {
        return ['Gems\\Snippets\\Ask\\ShowAllOpenSnippet'];
    }

    /**
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Show all open tokens - use lastname');
    }
}
