<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class that extends Array object to add Gems specific functions.
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Project_ProjectSettings extends ArrayObject
{
    /**
     * The db adapter for the responses
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_responsesDb;

    /**
     * The default session time out for this project in seconds.
     *
     * Can be overruled in sesssion.idleTimeout
     *
     * @var int
     */
    protected $defaultSessionTimeout = 1800;

    /**
     * The minimum length for the password of a super admin
     * on a production server.
     *
     * @var int
     */
    protected $minimumSuperPasswordLength = 10;

    /**
     * Array of required keys. Give a string value for root keys
     * or name => array() values for required subs keys.
     *
     * Deeper levels are not supported at the moment.
     *
     * @see checkRequiredValues()
     *
     * @var array
     */
    protected $requiredKeys = array(
        'css' => array('gems'),
        'locale' => array('default'),
        'salt',
        );

    /**
     * Creates the object and checks for required values.
     *
     * @param mixed $array
     */
    public function __construct($array)
    {
        // Convert to array when needed
        if ($array instanceof Zend_Config) {
            $array = $array->toArray();
        } elseif ($array instanceof ArrayObject) {
            $array = $array->getArrayCopy();
        } elseif (! is_array($array)) {
            $array = (array) $array;
        }

        // Now load default values for (new) keys and merge them with the ones provided by project.ini
        $projectValues = $array + $this->_getDefaultValues();

        parent::__construct($projectValues, ArrayObject::ARRAY_AS_PROPS);

        if (! ($this->offsetExists('name') && $this->offsetGet('name'))) {
            $this->offsetSet('name', GEMS_PROJECT_NAME);
        }

        $this->offsetSet('multiLocale', $this->offsetExists('locales') && (count($this->offsetGet('locales')) > 1));
    }

    /**
     * Set the default values for all keys.
     *
     * By doing this, we make sure the settings show up in the project information
     * with the defaults even if the settings was not present in the project.ini
     */
    public function _getDefaultValues()
    {

        return array(
            '>>> defaults <<<' => '>>> Below are default settings, since they were not found in your project.ini <<<',

            // What to do when user is going to or has answered a survey
            'askNextDelay' => -1, // No auto advance
            'askDelay'     => -1, // No auto advance

            // How to react to false token attempts
            'askThrottle'  => array(
                'period'    => 15 * 60, // Detection window: 15 minutes
                'threshold' => 15 * 20, // Threshold: 20 requests per minute
                'delay'     => 10       // Delay: 10 seconds
            ),

            'cache'        => 'apc',   // Use apc cache as default

            'organization' => array(
                'default'   => -1  // No default organization
            ),

            'idleTimeout' => $this->defaultSessionTimeout   // Sesison timeout default 1800
        );
    }

    /**
     * Add recursively the rules active for this specific set of codes.
     *
     * @param array $current The current (part)sub) array of $this->passwords to check
     * @param array $codes An array of code names that identify rules that should be used only for those codes.
     * @param array $rules The array that stores the activated rules.
     * @return void
     */
    protected function _getPasswordRules(array $current, array $codes, array &$rules)
    {
        foreach ($current as $key => $value) {
            if (is_array($value)) {
                // Only act when this is in the set of key values
                if (isset($codes[strtolower($key)])) {
                    $this->_getPasswordRules($value, $codes, $rules);
                }
            } else {
                $rules[$key] = $value;
            }
        }
    }

    /**
     * This function checks for the required project settings.
     *
     * Overrule this function or the $requiredParameters to add extra required settings.
     *
     * @see $requiredParameters
     *
     * @return void
     */
    public function checkRequiredValues()
    {
        $missing = array();
        foreach ($this->requiredKeys as $key => $names) {
            if (is_array($names)) {
                if (! ($this->offsetExists($key) && $this->offsetGet($key))) {
                    $subarray = array();
                } else {
                    $subarray = $this->offsetGet($key);
                }
                foreach ($names as $name) {
                    if (! isset($subarray[$name])) {
                        $missing[] = $key . '.' . $name;
                    }
                }
            } else {
                if (! ($this->offsetExists($names) && $this->offsetGet($names))) {
                    $missing[] = $names;
                }
            }
        }

        // Chek for https
        if (!MUtil_Https::on()) {
            if (! ($this->offsetExists('http') && $this->offsetGet('http'))) {
                MUtil_Https::enforce();
            }
        }

        if ($missing) {
            if (count($missing) == 1) {
                $error = sprintf("Missing required project setting: '%s'.", reset($missing));
            } else {
                $error = sprintf("Missing required project settings: '%s'.", implode("', '", $missing));
            }
            throw new Gems_Exception_Coding($error);
        }

        $superPassword = $this->getSuperAdminPassword();
        if (('production' === APPLICATION_ENV || 'acceptance' === APPLICATION_ENV) &&
                $this->getSuperAdminName() && $superPassword) {
            if (strlen($superPassword) < $this->minimumSuperPasswordLength) {
                $error = sprintf("Project setting 'admin.pwd' is shorter than %d characters. That is not allowed.", $this->minimumSuperPasswordLength);
                throw new Gems_Exception_Coding($error);
            }
        }
    }

    /**
     * Checks the super admin password, if it exists
     *
     * @param string $password
     * @return boolean True if the password is correct.
     */
    public function checkSuperAdminPassword($password)
    {
        return $password && ($password == $this->getSuperAdminPassword($password));
    }

    /**
     * Decrypt a string encrypted with encrypt()
     *
     * @param string $input String to decrypt
     * @param string $method The method used for encrypting (null = no encryption)
     * @return decrypted string
     */
    public function decrypt($input, $method = 'default')
    {
        if ((! $input) || (null === $method)) {
            return $input;
        }

        $decoded = base64_decode($input);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv      = substr($decoded, 0, $iv_size);
        $key     = md5($this->offsetExists('salt') ? $this->offsetGet('salt') : 'vadf2646fakjndkjn24656452vqk');

        // Remove trailing zero bytes!
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, substr($decoded, $iv_size), MCRYPT_MODE_CBC, $iv), "\0");
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $input String to decrypt
     * @param string $method The method used for encrypting (null = no encryption)
     * @return decrypted string
     */
    public function encrypt($input, $method = 'default')
    {
        if ((! $input) || (null === $method)) {
            return $input;
        }

        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
        $iv      = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $key     = md5($this->offsetExists('salt') ? $this->offsetGet('salt') : 'vadf2646fakjndkjn24656452vqk');

        return base64_encode($iv . mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $input, MCRYPT_MODE_CBC, $iv));
    }
    
    /**
     * Calculate the delay between surveys being asked for this request. Zero means forward
     * at once, a negative value means wait forever.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param boolean $wasAnswered When true use the ask delay
     * @return int -1 means waiting indefinitely
     */
    public function getAskDelay(Zend_Controller_Request_Abstract $request, $wasAnswered)
    {
        if ($request->getParam('delay_cancelled', false)) {
            return -1;
        }

        $delay = $request->getParam('delay', null);
        if (null != $delay) {
            return $delay;
        }

        if ($wasAnswered) {
            if ($this->offsetExists('askNextDelay')) {
                return $this->offsetGet('askNextDelay');
            }
        } else {
            if ($this->offsetExists('askDelay')) {
                return $this->offsetGet('askDelay');
            }
        }

        return -1;
    }

    /**
     * Returns an array with throttling settings for the ask
     * controller
     *
     * @return array
     */
    public function getAskThrottleSettings()
    {
        // Check for the 'askThrottle' config section
        if (!empty($this->askThrottle)) {
            return $this->askThrottle;
        } else {
            // Set some sensible defaults
            // Detection window: 15 minutes
            // Threshold: 20 requests per minute
            // Delay: 10 seconds
            $throttleSettings = array(
                'period'     => 15 * 60,
                'threshold'	 => 15 * 20,
                'delay'      => 10
            );
        }
    }

    /**
     * Get the specified cache method from project settings
     *
     * @return string
     */
    public function getCache()
    {
        if ($this->offsetExists('cache')) {
            $cache = $this->offsetGet('cache');
        } else {
            $cache = 'apc';
        }

        return $cache;
    }

    /**
     * Get the specified role for the console user from the project setttings.
     *
     * If the role is not defined (or does not exist) running GemsTracker in
     * console mode requires the users login name, organization and password to
     * be specified on the command line.
     *
     * @return string
     */
    public function getConsoleRole()
    {
        if ($this->offsetExists('console')) {
            $cons = $this->offsetGet('console');

            if (isset($cons['role'])) {
                return $cons['role'];
            }
        }

        return false;
    }

    /**
     * The site url during command line actions
     *
     * @return string
     */
    public function getConsoleUrl()
    {
        if ($this->offsetExists('console') && isset($this->console['url'])) {
            return trim($this->console['url']);
        }

        return 'localhost';
    }

    /**
     * Returns an (optional) default organization from the project settings
     *
     * @return int Organization number or -1 when not set
     */
    public function getDefaultOrganization()
    {
        if ($this->offsetExists('organization')) {
            $orgs = $this->offsetGet('organization');

            if (isset($orgs['default'])) {
                return $orgs['default'];
            }
        }

        return -1;
    }

    /**
     * Returns an (optional) default track id from the project settings
     *
     * Usually used in by single track project
     *
     * @return int Organization number or -1 when not set
     */
    public function getDefaultTrackId()
    {
        return $this->offsetExists('trackId') ? $this->offsetGet('trackId') : null;
    }

    /**
     * Returns the public description of this project.
     *
     * @return string
     */
    public function getDescription()
    {
        if ($this->offsetExists('description')) {
            return $this->offsetGet('description');
        } else {
            return $this->offsetGet('name');
        }
    }

    /**
     * Returns the documentation url
     *
     * @return string
     */
    public function getDocumentationUrl()
    {
        if (isset($this['contact'], $this['contact']['docsUrl'])) {
            return $this['contact']['docsUrl'];
        }
    }

    /**
     * The the email BCC address - if any
     *
     * @return string
     */
    public function getEmailBcc()
    {
        if ($this->offsetExists('email') && isset($this->email['bcc'])) {
            return trim($this->email['bcc']);
        }
    }

    /**
     * Should all mail be bounced to the sender?
     *
     * @return boolean
     */
    public function getEmailBounce()
    {
        if ($this->offsetExists('email') && isset($this->email['bounce'])) {
            return (boolean) $this->email['bounce'];
        }
        return false;
    }

    /**
     * The default Email Template for Create Account
     *
     * @return string   Template Code
     */
    public function getEmailCreateAccount()
    {
        if ($this->offsetExists('email') && isset($this->email['createAccountTemplate'])) {
            return (string) $this->email['createAccountTemplate'];
        }
        return false;
    }

    /**
     * Check if multiple language mail templates is supported
     * @return boolean
     */
    public function getEmailMultiLanguage()
    {
        if ($this->offsetExists('email') && isset($this->email['multiLanguage'])) {
            return (boolean) $this->email['multiLanguage'];
        } else {
            return true;
        }
    }

    /**
     * The default Email template for Reset password
     *
     * @return string   Template Code
     */
    public function getEmailResetPassword()
    {
        if ($this->offsetExists('email') && isset($this->email['resetPasswordTemplate'])) {
            return (string) $this->email['resetPasswordTemplate'];
        }
        return false;
    }

    /**
     * Get the directory to use as the root for automatic import
     *
     * @return string
     */
    public function getFileImportRoot()
    {
        if ($this->offsetExists('fileImportRoot')) {
            return $this->offsetGet('fileImportRoot');
        }
        return GEMS_ROOT_DIR . '\var\auto_import';
    }

    /**
     * Returns the forum url
     *
     * @return string
     */
    public function getForumUrl()
    {
        if (isset($this['contact'], $this['contact']['forumUrl'])) {
            return $this['contact']['forumUrl'];
        }
    }

    /**
     * Returns the from address
     *
     * @return string E-Mail address
     */
    public function getFrom()
    {
        return $this->getSiteEmail();
    }

    /**
     * Returns the initial password specified for users - if any.
     *
     * @return String
     */
    public function getInitialPassword()
    {
        if (isset($this['password'], $this['password']['initialPassword'])) {
            return $this['password']['initialPassword'];
        } else {
            return null;
        }
    }

    /**
     * Get the local jQuery directory (without base url)
     *
     * Instead of e.g. google Content Delivery Network.
     *
     * @return boolean
     */
    public function getJQueryLocal()
    {
        if (isset($this['jquery'], $this['jquery']['local'])) {
            return $this['jquery']['local'];
        } else {
            return null;
        }
    }

    /**
     * Get the default locale
     * @return string locale
     */
    public function getLocaleDefault()
    {
        if ($this->offsetExists('locale') && isset($this->locale['default'])) {
            return (string) $this->locale['default'];
        }
    }

    /**
     * Get the logLevel to use with the Gems_Log
     *
     * Default settings is for development and testing environment to use Zend_Log::DEBUG and
     * for all other environments to use the Zend_Log::ERR level. This can be overruled by
     * specifying a logLevel in the project.ini
     *
     * Using a level higher than Zend_Log::ERR will output full error messages, traces and request
     * info to the logfile. Please be aware that this might introduce a security risk as passwords
     * might be written to the logfile in plain text.
     *
     * @return int The loglevel to use
     */
    public function getLogLevel()
    {
        if (isset($this['logLevel'])) {
            $logLevel = $this['logLevel'];
        } else {
            if ('development' == APPLICATION_ENV || 'testing' == APPLICATION_ENV) {
                $logLevel = Zend_Log::DEBUG;
            } else {
                $logLevel = Zend_Log::ERR;
            }
        }

        return (int) $logLevel;
    }

    /**
     * Return a long description, in the correct language if available
     *
     * @param string $language Iso code languahe
     * @return string
     */
    public function getLongDescription($language)
    {
        if (isset($this['longDescr' . ucfirst($language)])) {
            return $this['longDescr' . ucfirst($language)];
        }

        if (isset($this['longDescr'])) {
            return $this['longDescr'];
        }
    }

    /**
     * Array of field name => values for sending E-Mail
     *
     * @return array
     */
    public function getMailFields()
    {
        $result['project']              = $this->getName();
        $result['project_bcc']          = $this->getEmailBcc();
        $result['project_description']  = $this->getDescription();
        $result['project_from']         = $this->getFrom();

        return $result;
    }

    /**
     * Returns the manual url
     *
     * @return string
     */
    public function getManualUrl()
    {
        if (isset($this['contact'], $this['contact']['manualUrl'])) {
            return $this['contact']['manualUrl'];
        }
    }

    /**
     * Returns the public name of this project.
     *
     * @return string
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

    /**
     * Get the rules active for this specific set of codes.
     *
     * @param array $codes An array of code names that identify rules that should be used only for those codes.
     * @return array
     */
    public function getPasswordRules(array $codes)
    {
        // Process the codes array to a format better used for filtering
        $codes = array_change_key_case(array_flip(array_filter($codes)));
        // MUtil_Echo::track($codes);

        $rules = array();
        if ($this->offsetExists('passwords') && is_array($this->passwords)) {
            $this->_getPasswordRules($this->passwords, $codes, $rules);
        }

        return $rules;
    }

    /**
     * The response database with a table with one row for each token answer.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getResponseDatabase()
    {
        if ((!$this->_responsesDb) && $this->hasResponseDatabase()) {
            $adapter = $this['responses']['adapter'];

            if (isset($this['responses'], $this['responses']['params'])) {
                $options = $this['responses']['params'];

                if (! isset(
                        $options['charset'],
                        $options['host'],
                        $options['dbname'],
                        $options['username'],
                        $options['password']
                        )) {

                    $db = Zend_Registry::get('db');

                    if ($db instanceof Zend_Db_Adapter_Abstract) {
                        $options = $options + $db->getConfig();
                    }
                }
                $this->_responsesDb = Zend_Db::factory($adapter, $options);
            } else {
                $db = Zend_Registry::get('db');

                if ($db instanceof Zend_Db_Adapter_Abstract) {
                    $this->_responsesDb = $db;
                }
            }
        }

        return $this->_responsesDb;
    }

    /**
     * Timeout for sessions in seconds.
     *
     * @return int
     */
    public function getSessionTimeOut()
    {
        if ($this->offsetExists('session') && isset($this->session['idleTimeout'])) {
            return $this->session['idleTimeout'];
        } else {
            return $this->defaultSessionTimeout;
        }
    }

    /**
     * The site email address - if any
     *
     * @return string
     */
    public function getSiteEmail()
    {
        if ($this->offsetExists('email') && isset($this->email['site'])) {
            return trim($this->email['site']);
        }
    }

    /**
     * Returns the super admin name, if any
     *
     * @return string
     */
    public function getSuperAdminName()
    {
        if ($this->offsetExists('admin') && isset($this->admin['user'])) {
            return trim($this->admin['user']);
        }
    }

    /**
     * Returns the super admin password, if it exists
     *
     * @return string
     */
    protected function getSuperAdminPassword()
    {
        if ($this->offsetExists('admin') && isset($this->admin['pwd'])) {
            return trim($this->admin['pwd']);
        }
    }

    /**
     * Returns the super admin ip range, if it exists
     *
     * @return string
     */
    public function getSuperAdminIPRanges()
    {
        if ($this->offsetExists('admin') && isset($this->admin['ipRanges'])) {
            return $this->admin['ipRanges'];
        }
    }

    /**
     * Returns the support url
     *
     * @return string
     */
    public function getSupportUrl()
    {
        if (isset($this['contact'], $this['contact']['supportUrl'])) {
            return $this['contact']['supportUrl'];
        }
    }

    /**
     * Returns a salted hash optionally using the specified hash algorithm
     *
     * @param string $value The value to hash
     * @param string $algoritm Optional, hash() algorithm; uses md5() otherwise
     * @return string The salted hexadecimal hash, length depending on the algorithm (32 for md5, 128 for sha512.
     */
    public function getValueHash($value, $algorithm = null)
    {
        $salt = $this->offsetExists('salt') ? $this->offsetGet('salt') : '';

        if (false === strpos($salt, '%s')) {
            $salted = $salt . $value;
        } else {
            $salted = sprintf($salt, $value);
        }

        // MUtil_Echo::track($value, md5($salted));
        if (null == $algorithm) {
            return md5($salted, false);
        }

        return hash($algorithm, $value, false);
    }

    /**
     * True at least one support url exists.
     *
     * @return boolean
     */
    public function hasAnySupportUrl()
    {
        return isset($this['contact']) && (
                isset($this['contact']['docsUrl']) ||
                isset($this['contact']['forumUrl']) ||
                isset($this['contact']['manualUrl']) ||
                isset($this['contact']['supportUrl'])
            );
    }

    /**
     * True the bugs url exists.
     *
     * @return boolean
     */
    public function hasBugsUrl()
    {
        return isset($this['contact'], $this['contact']['bugsUrl']);
    }

    /**
     * True if an initial password was specified for users.
     *
     * @return boolean
     */
    public function hasInitialPassword()
    {
        return isset($this['password'], $this['password']['initialPassword']);
    }

    /**
     * True when a response database with a table with one row for each token answer should exist.
     *
     * @return boolean
     */
    public function hasResponseDatabase()
    {
        return (boolean) isset($this['responses'], $this['responses']['adapter']) && $this['responses']['adapter'];
    }

    /**
     * Is running GemsTracker from the console allowed
     *
     * If allowed you can call index.php from the command line.
     * Use -h as a parameter to get more info, e.g:
     * <code>
     * php.exe -f index.php -- -f
     * </code>
     * The -- is needed because otherwise the command is interpreted
     * as php.exe -h.
     *
     * @return string
     */
    public function isConsoleAllowed()
    {
        if ($this->offsetExists('console')) {
            $cons = $this->offsetGet('console');

            if (isset($cons['allow'])) {
                return (boolean) $cons['allow'];
            }
        }

        return false;
    }

    /**
     * Does this project use a local jQuery
     *
     * Instead of e.g. google Content Delivery Network.
     *
     * @return boolean
     */
    public function isJQueryLocal()
    {
        return isset($this['jquery'], $this['jquery']['local']);
    }

    /**
     * Does this project use a local Bootstrap
     *
     * Instead of e.g. google Content Delivery Network.
     *
     * @return boolean
     */
    public function isBootstrapLocal()
    {
        return isset($this['bootstrap'], $this['bootstrap']['local']);
    }

    /**
     * Is login shared between organizations (which therefore require
     * a unique staff logon id for each user, instead of for each
     * user within an organization).
     *
     * @return boolean
     */
    public function isLoginShared()
    {
        return isset($this['organization'], $this['organization']['sharedLogin']) &&
                $this['organization']['sharedLogin'];
    }

    /**
     * Is this project use a multi locale project
     *
     * @return boolean
     */
    public function isMultiLocale()
    {
        return (boolean) (isset($this['multiLocale']) && $this['multiLocale']);
    }

    /**
     * Is a valid until date required for each round in each track
     *
     * @return boolean
     */
    public function isValidUntilRequired()
    {
        return isset($this['track'], $this['track']['requireValidUntil']) && $this['track']['requireValidUntil'];
    }
}