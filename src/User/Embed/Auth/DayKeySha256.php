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
 * @since      Class available since version 1.8.8 01-Apr-2020 17:24:36
 */
class DayKeySha256 extends HourKeySha256
{
    /**
     * Format for date part of key function
     *
     * @var string
     */
    protected string $keyTimeFormat = 'Ymd';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    protected int $keyTimeValidRange = 0;

    /**
     *
     * @return string Something to display as label.
     */
    public function getLabel(): string
    {
        return sprintf($this->translator->_('Less safe: Daily valid valid key with %s'), $this->encryptionAlgorithm);
    }

}
