<?php
/**
 * @package    Gems
 * @subpackage Validate
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Validate;

use IPLib\Factory as IpFactory;
use IPLib\Range\RangeInterface;
use Laminas\Validator\AbstractValidator;

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
class IPRanges extends AbstractValidator
{
    /**
     * Error constants
     */
    const ERROR_INVALID_IP = 'invalidIPInRange';

    /**
     * Error messages
     * @var array
     */
    protected $messageTemplates = [
        self::ERROR_INVALID_IP => 'One or more IPs are illegal.'
    ];

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = [])
    {
        $result = true;

        $ranges = explode('|', $value);

        foreach ($ranges as $range) {
            if (($sep = strpos($range, '-')) !== false) {
                $range = IpFactory::getRangeFromBoundaries(substr($range, 0, $sep), substr($range, $sep + 1));

            } else {
                $range = IpFactory::parseRangeString($range);
            }
            if (! $range instanceof RangeInterface) {
                $result = false;
                break;
            }
        }

        if (!$result) {
            $this->error(self::ERROR_INVALID_IP);
        }

        return $result;
    }
}
