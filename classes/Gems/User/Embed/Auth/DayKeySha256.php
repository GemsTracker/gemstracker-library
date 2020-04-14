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
    protected $keyTimeFormat = 'Ymd';

    /**
     * The number of time periods on either side of the current that is allowed
     *
     * @var int
     */
    protected $keyTimeValidRange = 0;

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getLabel()
    {
        return sprintf($this->_('Less safe: Daily valid valid key with %s'), $this->encryptionAlgorithm);
    }

}
