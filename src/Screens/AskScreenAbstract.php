<?php

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Screens;

use Gems\Tracker\Token;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Screens
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 June 11, 2017 5:09:26 PM
 */
abstract class AskScreenAbstract implements AskScreenInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    )
    {
    }

    /**
     *
     * @param Token $token
     * @return array Added before all other parameters
     */
    public function getParameters(Token $token): array
    {
        return [];
    }

    /**
     *
     * @param Token $token
     * @return array|bool Array of snippets or false to use original
     */
    public function getSnippets(Token $token): array|bool
    {
        return false;
    }
}
