<?php
/**
 * @package    Gems
 * @subpackage Auth
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A wrapper to use any valid callback for authentication
 *
 * @package    Gems
 * @subpackage Auth
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Auth_Adapter_Callback implements \Zend_Auth_Adapter_Interface
{
    /**
     * The callback to use
     *
     * @var callback
     */
    private $_callback;

    /**
     * The identity to check
     *
     * @var string
     */
    private $_identity;

    /**
     * The optional parameters to pass to the callback
     *
     * @var array
     */
    private $_params;

    /**
     * Create an auth adapter from a callback
     *
     * Ideally the callback should return a \Zend_Auth_Result, when not it should
     * return true or false and in that case this adapter will wrap the result
     * in a \Zend_Auth_Result
     *
     * @param callback $callback A valid callback
     * @param string $identity The identity to use
     * @param array $params   Array of parameters needed for the callback
     */
    public function __construct($callback, $identity, $params = array())
    {
        $this->_callback = $callback;
        $this->_identity = $identity;
        $this->_params   = $params;
    }

    /**
     * Perform the authenticate attempt
     *
     * @return \Zend_Auth_Result
     */
    public function authenticate()
    {
        $result = call_user_func_array($this->_callback, $this->_params);
        if ( !($result instanceof \Zend_Auth_Result)) {
            if ($result === true) {
                $result = new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, $this->_identity);
            } else {
                $result = new \Zend_Auth_Result(\Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, $this->_identity);
            }
        }
        return $result;
    }
}