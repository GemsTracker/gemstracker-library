<?php

/**
 *
 * @package    Gems
 * @subpackage User\Validator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Expression copyright is undefined on line 42, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 */

namespace Gems\User\Validate;

use Gems\User\TwoFactor\TwoFactorAuthenticatorInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Validator
 * @copyright  Expression copyright is undefined on line 54, column 18 in Templates/Scripting/PHPClass.php.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.4 30-Jun-2018 20:18:58
 */
class TwoFactorAuthenticateValidator implements \Zend_Validate_Interface
{
    /**
     *
     * @var TwoFactorAuthenticatorInterface
     */
    private $_authenticator;

    /**
     *
     * @var string
     */
    private $_key;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    private $_translator;

    /**
     *
     * @var boolean
     */
    private $_valid = false;

    /**
     *
     * @param TwoFactorAuthenticatorInterface $authenticator
     * @param string $key User key
     * @param \Zend_Translate $translate
     */
    public function __construct(TwoFactorAuthenticatorInterface $authenticator, $key, \Zend_Translate $translate)
    {
        $this->_authenticator = $authenticator;
        $this->_key           = $key;
        $this->_translator    = $translate->getAdapter();
    }


    /**
     * Returns an array of messages that explain why the most recent isValid()
     * call returned false. The array keys are validation failure message identifiers,
     * and the array values are the corresponding human-readable message strings.
     *
     * If isValid() was never called or if the most recent isValid() call
     * returned true, then this method returns an empty array.
     *
     * @return array
     */
    public function getMessages()
    {
        if (! $this->_valid) {
            return [$this->_translator->_('This is not the correct two factor code!')];
        }
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @param  mixed $content
     * @return boolean
     * @throws \Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = array())
    {
        $this->_valid = $this->_authenticator->verify($this->_key, $value);

        return $this->_valid;
    }
}
