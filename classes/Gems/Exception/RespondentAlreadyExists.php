<?php

/**
 * @package    Gems
 * @subpackage Exception
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    default
 */

namespace Gems\Exception;

/**
 * Description of RespondentAlreadyExists
 *
 * @package    Gems
 * @subpackage Exception
 * @copyright  Copyright (c) 2019 Erasmus MC
 * @license    default
 * @since      Class available since version 
 */
class RespondentAlreadyExists extends \Gems_Exception {
    public const SAME = 'same uid and pid exists';
    public const OTHERUID = 'exists with other uid';
    public const OTHERPID = 'exists with other pid';
}
