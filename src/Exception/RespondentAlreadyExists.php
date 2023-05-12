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
class RespondentAlreadyExists extends \Gems\Exception {
    const SAME = 'same uid and pid exists';
    const OTHERUID = 'exists with other uid';
    const OTHERPID = 'exists with other pid';
}
