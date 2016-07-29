<?php

/**
 *
 * @package    Gems
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ValidPeriodEndValidator.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 8-mei-2015 19:55:39
 */
class Gems_Validate_ValidPeriodEndValidator extends \Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const DIRECTION_NEG = 'dirNeg';
    const DIRECTION_POS = 'dirPos';

    protected $_messageTemplates = array(
        self::DIRECTION_NEG => "End difference must earlier than start difference.",
        self::DIRECTION_POS => "End difference must later than start difference.",
    );

    private $_minField;

    public function __construct($minField, $minUnit, $maxUnit, $message)
    {

    }

    /**
     * Defined by \Zend_Validate_Interface
     *
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = array())
    {

    }
}
