<?php

/**
 *
 * @package    Gems
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

use Zend\Authentication\Result;

/**
 * Performs replacement of standard login failure texts
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class Gems_User_Validate_PasswordValidatorAbstract implements \Zend_Validate_Interface
{
    /**
     *
     * @var Zend\Authentication\Result
     */
    private $_authResult = null;

    /**
     * Default message for standard login fail.
     *
     * @var string
     */
    private $_message;

    /**
     *
     * @param string $message Default message for standard login fail.
     */
    public function __construct($message)
    {
        $this->_message = $message;
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
        if ($this->_authResult->isValid()) {
            return array();

        } else {
            $messages = $this->_authResult->getMessages();

            switch (count($messages)) {
                case 0:
                    return array($this->_message);

                case 1:
                    switch (reset($messages)) {
                        case 'Supplied credential is invalid.':
                        case 'Authentication failed.':
                            return array($this->_message);

                        // default:
                        // Intentional fall through
                    }

                default:
                    // Intentional fall through
                    return $messages;
            }
        }
    }

    /**
     * Set the result for this validator
     *
     * @param Zend\Authentication\Result $result
     * @return boolean True when valid
     */
    protected function setAuthResult(Result $result)
    {
        $this->_authResult = $result;

        return $this->_authResult->isValid();
    }
}
