<?php
/**
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Not used anymore, checked if we could use soap connection. As soap is no longer a reliable
 * interface in LimeSurvey it is deprecated for now.
 *
 * @deprecated
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @deprecated
 * @license    New BSD License
 */
class LimeSurveySoapClient extends \MUtil_Validate_Url
{
    /**
     * Error constants
     */
    const ERROR_NO_LS_SOAP = 'noLimeServerSoap';

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws \Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = array())
    {
        if (parent::isValid($value, $context)) {

            $value .= '/admin/lsrc.server.php';

            if (parent::isValid($value, $context)) {
                return true;
            }

            $this->_setValue($value);
            $this->_messageTemplates[self::ERROR_NO_LS_SOAP] = 'No Lime Survey SOAP connection on URL.';
            $this->_error(self::ERROR_NO_LS_SOAP);
        }
        return false;
    }
}
