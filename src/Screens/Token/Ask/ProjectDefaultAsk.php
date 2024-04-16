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
 * @since      Class available since version 1.8.2 11-Jul-2017 16:51:44
 */
class ProjectDefaultAsk extends AskScreenAbstract
{
    /**
     *
     * @inheritDoc
     */
    public function getScreenLabel(): string: string
    {
        return $this->translator->_('(default project specific token ask screen)');
    }
}
