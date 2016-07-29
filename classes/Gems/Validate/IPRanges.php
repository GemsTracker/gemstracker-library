<?php
/**
 * @package    Gems
 * @subpackage Validate
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Not used anymore, checked if we could use soap connection. As soap is no longer a reliable
 * interface in LimeSurvey it is deprecated for now.
 *
 * @package    Gems
 * @subpackage Validate
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Validate_IPRanges extends \Zend_Validate_Abstract
{
    /**
     * Error constants
     */
    const ERROR_INVALID_IP = 'invalidIPInRange';

    /**
     * Error messages
     * @var array
     */
    protected $_messageTemplates = array(
        self::ERROR_INVALID_IP => 'One or more IPs are illegal.'
    );

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @todo ip2long is broken on Windows, find a replacement
     */
    public function isValid($value, $context = array())
    {
        $result = true;

        $ranges = explode('|', $value);

        foreach ($ranges as $range) {
            if (($sep = strpos($range, '-')) !== false) {
                $min = ip2long(substr($range, 0, $sep));
                $max = ip2long(substr($range, $sep + 1));

                if ($min === false || $max === false) {
                    $result = false;
                }
            } else if (ip2long($range) === false) {
                $result = false;
            }
        }

        if (!$result) {
            $this->_error(self::ERROR_INVALID_IP);
        }

        return $result;
    }
}
