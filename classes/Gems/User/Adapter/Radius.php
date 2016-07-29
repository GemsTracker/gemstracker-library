<?php
/**
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

include_once 'Radius/radius.class.php';

/**
 * Implements Radius authentication using Pure PHP radius class as downloaded
 * from http://www.phpkode.com/scripts/item/pure-php-radius-class/
 *
 * This class is a wrapper around the Pure PHP radius class by
 *          SysCo systemes de communication sa
 * see library folder for specific copyright and license information
 *
 * Check the constructor for required config parameters.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_User_Adapter_Radius implements \Zend_Auth_Adapter_Interface
{
	/**
	 * $_identity - Identity value
	 *
	 * @var string
	 */
	protected $_identity = null;

	/**
	 * $_credential - Credential values
	 *
	 * @var string
	 */
	protected $_credential = null;

	/**
	 * $_authenticateResultInfo
	 *
	 * @var array
	 */
	protected $_authenticateResultInfo = null;

	/**
	 * IP addres of the Radius server
	 *
	 * @var string
	 */
	protected $_ip = null;

	/**
	 * Shared secret for the Radius server
	 *
	 * @var string
	 */
	protected $_sharedSecret = null;

	/**
	 * Suffix for Radius server
	 *
	 * @var string
	 */
	protected $_suffix = null;

	/**
	 * Timeout for Radius server in seconds
	 * @var int
	 */
	protected $_timeout = 5;

	/**
	 * Authentication port for Radius server
	 *
	 * @var int
	 */
	protected $_authenticationPort = 1812;

	/**
	 * Accounting port for Radius server
	 *
	 * @var int
	 */
	protected $_accountingPort = 1813;

	/**
	 * __construct() - Sets configuration options
	 *
	 * @param  string                   $identity
	 * @param  string                   $credential
	 * @return void
	 */

	/**
	 * The actual Radius object
	 *
	 * @var Radius
	 */
	protected $_radius = null;

	/**
	 * Constructor
	 *
	 * Supported params for $config are:
	 * - ip                 = Ip address of the Radius server.
	 * - sharedsecret       = The shared secret.
	 * - suffix             = Suffix for the Radius server.
	 * - timeout            = Timeout in seconds, default = 5.
	 * - authenticationport = Authentication port, default = 1812.
	 * - accountingport     = Accounting port, default = 1813.
     *
     * The minimal options are ip and shared secret, all others are optional
	 *
	 * @param  array $config Array of user-specified config options.
	 * @return void
	 */
	public function __construct($config = array())
	{
		if ($config instanceof \Zend_Config) {
			$config = $config->toArray();
		}

		if (!is_array($config)) {
			$exception = "Constructor should be passed and array with at least ip and sharedsecret.";

			/**
			 * @see \Zend_Auth_Adapter_Exception
			 */
			throw new \Zend_Auth_Adapter_Exception($exception);
		}

		foreach ($config as $key => $value) {
			switch (strtolower($key)) {
				case 'ip':
					$this->_ip = $value;
					break;
				case 'sharedsecret':
					$this->_sharedSecret = $value;
					break;
				case 'suffix':
					$this->_suffix = $value;
					break;
				case 'timeout':
					$this->_timeout = $value;
					break;
				case 'authenticationport':
					$this->_authenticationPort = $value;
					break;
				case 'accountingport':
					$this->_accountingPort = $value;
					break;
				default:
					// ignore unrecognized configuration directive
					break;
			}
		}
	}

	/**
	 * setIdentity() - set the value to be used as the identity
	 *
	 * @param  string $value
	 * @return \Gems_User_Adapter_Radius Provides a fluent interface
	 */
	public function setIdentity($value)
	{
		$this->_identity = $value;
		return $this;
	}

	/**
	 * setCredential() - set the credential value to be used
	 *
	 * @param  string $credential
	 * @return \Gems_User_Adapter_Radius Provides a fluent interface
	 */
	public function setCredential($credential)
	{
		$this->_credential = $credential;
		return $this;
	}

	/**
	 * authenticate() - defined by \Zend_Auth_Adapter_Interface.  This method is called to
	 * attempt an authenication.  Previous to this call, this adapter would have already
	 * been configured with all necessary information to successfully connect to a Radius
	 * server and attempt to find a record matching the provided identity.
	 *
	 * @throws \Zend_Auth_Adapter_Exception if answering the authentication query is impossible
	 * @return \Zend_Auth_Result
	 */
	public function authenticate()
	{
		$this->_authenticateSetup();

		if ($this->_radius->AccessRequest($this->_identity,$this->_credential)) {
			$this->_authenticateResultInfo['code'] = \Zend_Auth_Result::SUCCESS;
			$this->_authenticateResultInfo['messages'][] = 'Authentication successful.';
		} else {
			$this->_authenticateResultInfo['code'] = \Zend_Auth_Result::FAILURE;
			$this->_authenticateResultInfo['messages'][] = 'Authentication failed.';
		}

		$authResult = $this->_authenticateCreateAuthResult();
		return $authResult;
	}

	/**
	 * _authenticateSetup() - This method abstracts the steps involved with making sure
	 * that this adapter was indeed setup properly with all required peices of information.
	 *
	 * @throws \Gems_Exception_Coding - in the event that setup was not done properly
	 * @return true
	 */
	protected function _authenticateSetup()
	{
		$exception = null;

		if ($this->_ip === null) {
			$exception = 'An ip address must be specified for use with the \Gems_User_Adapter_Radius authentication adapter.';
		} elseif ($this->_sharedSecret === null) {
			$exception = 'A shared secret must be specified for use with the \Gems_User_Adapter_Radius authentication adapter.';
		} elseif ($this->_identity == '') {
			$exception = 'A value for the identity was not provided prior to authentication with \Gems_User_Adapter_Radius.';
		} elseif ($this->_credential === null) {
			$exception = 'A credential value was not provided prior to authentication with \Gems_User_Adapter_Radius.';
		}

		if (null !== $exception) {
			/**
			 * @see \Gems_Exception_Coding
			 */
			throw new \Gems_Exception_Coding($exception);
		}

		$this->_authenticateResultInfo = array(
            'code'     => \Zend_Auth_Result::FAILURE,
            'identity' => $this->_identity,
            'messages' => array()
		);

		$this->_radius = new Radius($this->_ip, $this->_sharedSecret,$this->_suffix,$this->_timeout,$this->_authenticationPort,$this->_accountingPort);

		return true;
	}

	/**
	 * _authenticateCreateAuthResult() - This method creates a \Zend_Auth_Result object
	 * from the information that has been collected during the authenticate() attempt.
	 *
	 * @return \Zend_Auth_Result
	 */
	protected function _authenticateCreateAuthResult()
	{
		return new \Zend_Auth_Result(
		$this->_authenticateResultInfo['code'],
		$this->_authenticateResultInfo['identity'],
		$this->_authenticateResultInfo['messages']
		);
	}
}