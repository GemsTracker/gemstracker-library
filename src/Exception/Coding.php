<?php
/**
 *
 * \Gems Coding exception
 *
 * @package    Gems
 * @subpackage Exception
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Exception;

/**
 * \Gems Coding exception
 *
 * @package    Gems
 * @subpackage Exception
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Coding extends \Gems\Exception
{
    public function __construct($msg = '', $code = 200, \Exception $previous = null)
    {
        parent::__construct($msg, $code, $previous, 'This is a setup error, please warn the programmer if you see this.');
    }
}
