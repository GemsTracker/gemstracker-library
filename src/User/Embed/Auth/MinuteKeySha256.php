<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed\Auth;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version v2.0.47, 01-07-2025
 */
class MinuteKeySha256 extends TimeKeySha256Abstract
{
    /**
     * Format for date part of key function
     *
     * @var string
     */
    protected string $keyTimeFormat = 'YmdHi';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    protected int $keyTimeValidRange = 1;

    /**
     *
     * @return string Something to display as label.
     */
    public function getLabel(): string
    {
        return sprintf($this->translator->_('Minute valid key with %s'), $this->encryptionAlgorithm);
    }
}
