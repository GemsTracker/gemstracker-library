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

use Gems\Tracker\Token;

/**
 *
 * @package    Gems
 * @subpackage Screens\Token\Ask
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ShowAllOpenAskPrivate extends ShowAllOpenAsk
{
    /**
     * @inheritDoc
     */
    public function getParameters(Token $token): array
    {
        return ['showLastName' => false];
    }

    /**
     * @inheritDoc
     */
    public function getScreenLabel(): string
    {
        return $this->translator->_('Show all open tokens - without lastname');
    }
}