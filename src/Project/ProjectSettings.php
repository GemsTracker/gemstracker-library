<?php


namespace Gems\Project;

/**
 * This class allows objects to work as arrays.
 * @link https://php.net/manual/en/class.arrayobject.php
 * @template TKey
 * @template TValue
 * @template-implements \IteratorAggregate<TKey, TValue>
 * @template-implements \ArrayAccess<TKey, TValue>
 */
class ProjectSettings extends \ArrayObject
{
    /**
     * The db adapter for the responses
     *
     * @var \Zend_Db_Adapter_Abstract
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
    protected $requiredKeys = [
        'css' => ['gems'],
        'locale' => ['default'],
        'salt',
    ];

    /**
     * Creates the object and checks for required values.
     *
     * @param mixed $array
     */
    public function __construct(array $config)
    {
        parent::__construct($config, \ArrayObject::ARRAY_AS_PROPS);

        $this->offsetSet('multiLocale', $this->offsetExists('locales') && (count($this->offsetGet('locales')) > 1));
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
        $missing = [];
        foreach ($this->requiredKeys as $key => $names) {
            if (is_array($names)) {
                if (! ($this->offsetExists($key) && $this->offsetGet($key))) {
                    $subarray = [];
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
        if (!\MUtil\Https::on()) {
            if ($this->isHttpsRequired()) {
                \MUtil\Https::enforce();
            }
        }

        if ($missing) {
            if (count($missing) == 1) {
                $error = sprintf("Missing required project setting: '%s'.", reset($missing));
            } else {
                $error = sprintf("Missing required project settings: '%s'.", implode("', '", $missing));
            }
            throw new \Gems\Exception\Coding($error);
        }

        $superPassword = $this->getSuperAdminPassword();
        if (('production' === $this['app']['env'] || 'acceptance' === $this['app']['env']) &&
                $this->getSuperAdminName() && $superPassword) {
            if (strlen($superPassword) < $this->minimumSuperPasswordLength) {
                $error = sprintf("Project setting 'admin.pwd' is shorter than %d characters. That is not allowed.", $this->minimumSuperPasswordLength);
                throw new \Gems\Exception\Coding($error);
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
        return $password && ($password == $this->getSuperAdminPassword());
    }

    /**
     * Decrypt a string encrypted with encrypt()
     *
     * @param string $input String to decrypt
     * @param string $key Optional key. If null project salt will be used
     * @return string decrypted string
     */
    public function decrypt($input, $key=null)
    {
        if (! $input) {
            return $input;
        }

        $methods = $this->getEncryptionMethods();
        if (':' == $input[0]) {
            list($empty, $mkey, $base64) = explode(':', $input, 3);

            if (! isset($methods[$mkey])) {
                $error = sprintf("Encryption method '%s' not defined in project.ini.", $mkey);
                throw new \Gems\Exception\Coding($error);
            }

            $method = $methods[$mkey];
        } else {
            $mkey   = 'AES-256-CBC';
            $base64 = $input;
            $method = $mkey;
        }

        if ($key === null) {
            $key = $this->getEncryptionSaltKey();
        }

        $decoded = base64_decode($base64);
        $output = $this->decryptOpenSsl($decoded, $method, $key);

        if (false === $output) {
            return $input;
        } else {
            return $output;
        }
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $input String to decrypt
     * @param string $method The cipher method, one of openssl_get_cipher_methods().
     * @param string $key Key to use for decryption
     * @return string decrypted string of false
     */
    protected function decryptOpenSsl($input, $method, $key)
    {
        $ivlen = openssl_cipher_iv_length($method);
        $iv    = substr($input, 0, $ivlen);

        return openssl_decrypt(substr($input, $ivlen), $method, $key, 0, $iv);
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $input String to decrypt
     * @param string $key Optional key. If null project salt will be used
     * @return string encrypted string
     */
    public function encrypt($input, $key=null)
    {
        if (! $input) {
            return $input;
        }

        $methods = $this->getEncryptionMethods();
        $method  = reset($methods);
        $mkey    = key($methods);
        if ($key === null) {
            $key = $this->getEncryptionSaltKey();
        }

        $result = $this->encryptOpenSsl($input, $method, $key);

        return ":$mkey:" . base64_encode($result);
    }

    /**
     * Reversibly encrypt a string
     *
     * @param string $input String to encrypt
     * @param string $method The cipher method, one of openssl_get_cipher_methods().
     * @param string $key Key used for encryption
     * @return string encrypted string
     */
    protected function encryptOpenSsl($input, $method, $key)
    {
        $ivlen = openssl_cipher_iv_length($method);
        $iv    = openssl_random_pseudo_bytes($ivlen);

        return $iv . openssl_encrypt($input, $method, $key, 0, $iv);
    }

    /**
     * Array of hosts allowed to post data to this project
     *
     * @return array
     * @deprecated since version 2 use OrganizationRepository
     */
    public function getAllowedHosts()
    {
        if (isset($this['allowedSourceHosts'])) {
            return (array) $this['allowedSourceHosts'];
        }

        return array();
    }

    /**
     * Calculate the delay between surveys being asked for this request. Zero means forward
     * at once, a negative value means wait forever.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param boolean $wasAnswered When true use the ask delay
     * @return int -1 means waiting indefinitely
     */
    public function getAskDelay(\Zend_Controller_Request_Abstract $request, $wasAnswered)
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
        if (empty($this->askThrottle)) {
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
        return $this->askThrottle;
    }

    /**
     * Returns the documentation url
     *
     * @return string
     */
    public function getBugsUrl()
    {
        if (isset($this['contact'], $this['contact']['bugsUrl'])) {
            return $this['contact']['bugsUrl'];
        }

        return 'https://github.com/GemsTracker/gemstracker-library/issues';
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
     * The logfile for cron jobs
     *
     * @return string
     */
    public function getCronLogfile()
    {
        if (! ($this->offsetExists('cron') && isset($this->cron['logfile']))) {
            return null;
        }

        $file = trim($this->cron['logfile']);
        if (\MUtil\File::isRootPath($file)) {
            return $file;
        }
        return $this['app']['rootDir'] . '/var/logs/' . $file;
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
     * @return string|null
     */
    public function getDocumentationUrl()
    {
        if (isset($this['contact'], $this['contact']['docsUrl'])) {
            return $this['contact']['docsUrl'];
        }
        return null;
    }

    /**
     * The the email BCC address - if any
     *
     * @return string|null
     */
    public function getEmailBcc()
    {
        if ($this->offsetExists('email') && isset($this->email['bcc'])) {
            return trim($this->email['bcc']);
        }
        return null;
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
     *
     * @return array (stored) key => openssl_get_cipher_method used
     */
    protected function getEncryptionMethods()
    {
        if (isset($this['security'], $this['security']['methods'])) {
            // reverse so first item is used as default
            $output = array_reverse($this['security']['methods']);
        } else {
            $output = [];
        }
        if (! isset($output['null'])) {
            $output['null'] = 'null';
        }

        return $output;
    }

    /**
     * Get stored encryption keys by name
     *
     * @param $keyName string name of the stored key
     * @return string|null Stored key, or null if not found
     */
    public function getEncryptionKey($keyName)
    {
        if (isset($this['security'], $this['security']['keys'], $this['security']['keys'][$keyName])) {
            return $this['security']['keys'][$keyName];
        }

        return null;
    }

    /**
     *
     * @return string The salt key
     */
    protected function getEncryptionSaltKey()
    {
        return md5($this->offsetExists('salt') ? $this->offsetGet('salt') : 'vadf2646fakjndkjn24656452vqk');
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
        return $this['app']['rootDir'] . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR .  'auto_import';
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
     * Returns the from address
     *
     * @return string E-Mail address
     */
    public function getImageDir()
    {
        if (isset($this['imagedir']) && $this['imagedir']) {
            return $this['imagedir'];
        }

        return 'gems/images';
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
     * Get the LDAP query settings
     *
     * @return array|null Settings
     */
    public function getLdapSettings()
    {
        if ($this->offsetExists('ldap')) {
            return $this->ldap;
        }
        return null;
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
        return null;
    }

    /**
     * Get the logLevel to use with the \Gems\Log
     *
     * Default settings is for development and testing environment to use Zend_Log::DEBUG and
     * for all other environments to use the \Zend_Log::ERR level. This can be overruled by
     * specifying a logLevel in the project.ini
     *
     * Using a level higher than \Zend_Log::ERR will output full error messages, traces and request
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
            $logLevel = $this->getLogLevelDefault();
        }

        return (int) $logLevel;
    }

    /**
     * Return the default logLevel to use with the \Gems\Log
     *
     * Default settings is for development and testing environment to use Zend_Log::DEBUG and
     * for all other environments to use the \Zend_Log::ERR level. This can be overruled by
     * specifying a logLevel in the project.ini
     *
     * @return int The loglevel to use
     */
    public function getLogLevelDefault() {
        if ('development' == $this['app']['env'] || 'testing' == $this['app']['env']) {
            $logLevel = \Zend_Log::DEBUG;
        } else {
            $logLevel = \Zend_Log::ERR;
        }

        return $logLevel;
    }

    /**
     * Return a long description, in the correct language if available
     *
     * @param string $language Iso code languahe
     * @return string|null
     */
    public function getLongDescription($language)
    {
        if (isset($this['longDescr' . ucfirst($language)])) {
            return $this['longDescr' . ucfirst($language)];
        }

        if (isset($this['longDescr'])) {
            return $this['longDescr'];
        }
        return null;
    }

    /**
     * Array of field name => values for sending E-Mail
     *
     * @return array
     */
    public function getMailFields()
    {
        $result['project']              = $this->getName();
        // $result['project_bcc']          = $this->getEmailBcc();
        $result['project_description']  = $this->getDescription();
        $result['project_from']         = $this->getFrom();

        return $result;
    }

    /**
     * Returns the manual url
     *
     * @return string|null
     */
    public function getManualUrl()
    {
        if (isset($this['contact'], $this['contact']['manualUrl'])) {
            return $this['contact']['manualUrl'];
        }
        return null;
    }

    /**
     * Get the form address for monitor messages
     *
     * @param string $name Optional section name
     * @return string
     */
    public function getMonitorFrom($name = null)
    {
        if ($name) {
            if (isset($this['monitor'], $this['monitor'][$name], $this['monitor'][$name]['from']) &&
                    trim($this['monitor'][$name]['from'])) {
                return $this['monitor'][$name]['from'];
            }
        }
        if (isset($this['monitor'], $this['monitor']['default'], $this['monitor']['default']['from']) &&
                trim($this['monitor']['default']['from'])) {
            return $this['monitor']['default']['from'];
        }

        $email = $this->getSiteEmail();
        if ($email) {
            return $email;
        }

        return 'noreply@gemstracker.org';
    }

    /**
     * Get the period for monitor messages, optionally for a name
     *
     * @param string $name Optional section name
     * @return string
     */
    public function getMonitorPeriod($name = null)
    {
        if ($name) {
            if (isset($this['monitor'], $this['monitor'][$name], $this['monitor'][$name]['period']) &&
                    strlen(trim($this['monitor'][$name]['period']))) {
                return $this['monitor'][$name]['period'];
            }
        }

        if (isset($this['monitor'], $this['monitor']['default'], $this['monitor']['default']['period']) &&
                strlen(trim($this['monitor']['default']['period']))) {
            return $this['monitor']['default']['period'];
        }

        return '25h';
    }

    /**
     * Get the to addresses for monitor messages, optionally for a name
     *
     * @param string $name Optional section name
     * @return string
     */
    public function getMonitorTo($name = null)
    {
        if ($name) {
            if (isset($this['monitor'], $this['monitor'][$name], $this['monitor'][$name]['to']) &&
                    trim($this['monitor'][$name]['to'])) {
                return $this['monitor'][$name]['to'];
            }
        }

        if (isset($this['monitor'], $this['monitor']['default'], $this['monitor']['default']['to']) &&
                trim($this['monitor']['default']['to'])) {
            return $this['monitor']['default']['to'];
        }

        return null;
    }

    /**
     * Returns the public name of this project.
     *
     * @return string
     */
    public function getName()
    {
        if ($this->offsetExists('app') && isset($this->app['name'])) {
            return $this->app['name'];
        }
        return 'GemsTracker';
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
        // \MUtil\EchoOut\EchoOut::track($codes);

        $rules = array();
        if ($this->offsetExists('passwords') && is_array($this->passwords)) {
            $this->_getPasswordRules($this->passwords, $codes, $rules);
        }

        return $rules;
    }

    /**
     * Get additional meta headers
     *
     * @return array Of http-equiv => content values for meta tags
     */
    public function getMetaHeaders()
    {
        if ($this->offsetExists('meta') && is_array($this->meta)) {
            $meta = $this->meta;
        } else {
            $meta = [];
        }
        if (!array_key_exists('Content-Type', $meta)) {
            $meta['Content-Type'] = 'text/html;charset=UTF-8';
        }

        // Remove null/empty values, this allow you to remove the Content-Type by making it empty.
        return array_filter($meta);
    }

    /**
     * Get a redis DSN
     *
     * @return string|false
     */
    public function getRedisDsn()
    {
        if ($this->offsetExists('redis') && isset($this->redis['dsn'])) {
            return $this->redis['dsn'];
        }
        return false;
    }

    /**
     * The response database with a table with one row for each token answer.
     *
     * @return \Zend_Db_Adapter_Abstract
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

                    $db = \Zend_Registry::get('db');

                    if ($db instanceof \Zend_Db_Adapter_Abstract) {
                        $options = $options + $db->getConfig();
                    }
                }
                $this->_responsesDb = \Zend_Db::factory($adapter, $options);
            } else {
                $db = \Zend_Registry::get('db');

                if ($db instanceof \Zend_Db_Adapter_Abstract) {
                    $this->_responsesDb = $db;
                }
            }
        }

        return $this->_responsesDb;
    }

    /**
     * Get additional response headers
     *
     * @return array Of name => value for HTTP response headers
     */
    public function getResponseHeaders()
    {
        if ($this->offsetExists('headers') && is_array($this->headers)) {
            $headers = $this->headers;
        } else {
            $headers = [];
        }
        if (!array_key_exists('X-UA-Compatible', $headers)) {
            $headers['X-UA-Compatible'] = 'IE=edge,chrome=1';
        }

        // Remove null/empty values, this allow you to remove the X-UA-Compatible by making it empty.
        return array_filter($headers);
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
     * @return string|null
     */
    public function getSiteEmail()
    {
        if ($this->offsetExists('email') && isset($this->email['site'])) {
            return trim($this->email['site']);
        }
        return null;
    }

     /**
     * Should staff mail be bounced to the sender?
     *
     * @return boolean
     */
    public function getStaffBounce()
    {
        if ($this->offsetExists('email') && isset($this->email['staffBounce'])) {
            return (boolean) $this->email['staffBounce'];
        }
        return $this->getEmailBounce();
    }

   /**
     * Returns the super admin name, if any
     *
     * @return string|null
     */
    public function getSuperAdminName()
    {
        if ($this->offsetExists('admin') && isset($this->admin['user'])) {
            return trim($this->admin['user']);
        }
        return null;
    }

    /**
     * Returns the super admin password, if it exists
     *
     * @return string|null
     */
    protected function getSuperAdminPassword()
    {
        if ($this->offsetExists('admin') && isset($this->admin['pwd'])) {
            return trim($this->admin['pwd']);
        }
        return null;
    }

    /**
     * Get the super admin two factor authentication ip exclude range
     *
     * @return string|null
     */
    public function getSuperAdminTwoFactorIpExclude()
    {
        if ($this->offsetExists('admin') && isset($this->admin['2fa'], $this->admin['2fa']['exclude'])) {
            return trim($this->admin['2fa']['exclude']);
        }
        return null;
    }

    /**
     * Get the super admin two factor authentication key
     *
     * @return string|null
     */
    public function getSuperAdminTwoFactorKey()
    {
        if ($this->offsetExists('admin') && isset($this->admin['2fa'], $this->admin['2fa']['key'])) {
            return trim($this->admin['2fa']['key']);
        }
        return null;
    }

    /**
     * Returns the super admin ip range, if it exists
     *
     * @return string|null
     */
    public function getSuperAdminIPRanges()
    {
        if ($this->offsetExists('admin') && isset($this->admin['ipRanges'])) {
            return $this->admin['ipRanges'];
        }
        return null;
    }

    /**
     * Returns the support url
     *
     * @return string|null
     */
    public function getSupportUrl()
    {
        if (isset($this['contact'], $this['contact']['supportUrl'])) {
            return $this['contact']['supportUrl'];
        }
        return null;
    }

    /**
     * Return an array with TwoFactor Methods, corresponding to the classnames in User\TwoFactor
     *
     * @return string[]
     */
    public function getTwoFactorMethods()
    {
        if (isset($this['twoFactor'], $this['twoFactor']['methods'])) {
            $methods = [];
            foreach($this['twoFactor']['methods'] as $authenticator=>$authSettings) {
                // filter specifically disabled methods
                if ($authSettings != 0) {
                    $methods[] = $authenticator;
                }
            }

            return $methods;
        }

        // Return GoogleAuthenticator as default when nothing is set
        return ['GoogleAuthenticator'];
    }

    /**
     * @return array
     */
    public function getTwoFactorMethodSettings()
    {
        if (isset($this['twoFactor'], $this['twoFactor']['methods'])) {
            return $this['twoFactor']['methods'];
        }

        return [];
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

        // \MUtil\EchoOut\EchoOut::track($value, md5($salted));
        if (null == $algorithm) {
            return md5($salted, false);
        }

        return hash($algorithm, $value, false);
    }

    /**
     * True at least one support url exists.
     *
     * @return boolean
     * @deprecated Since 1.8.2 No longer in use
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
     * @deprecated Since 1.8.2 No longer in use
     */
    public function hasBugsUrl()
    {
        return isset($this['contact'], $this['contact']['bugsUrl']) ? $this['contact']['bugsUrl'] : true;
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
     * @return bool True if the program is using a nonce
     */
    public function hasNonce()
    {
        return isset($this->headers['Content-Security-Policy']) && strpos($this->headers['Content-Security-Policy'], '$scriptNonce') !== false;
    }
    
    /**
     * True when a response database with a table with one row for each token answer should exist.
     *
     * @return bool
     */
    public function hasResponseDatabase(): bool
    {
        return isset($this['responseData']['enabled']) && $this['responseData']['enabled'] === true;
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
     * Is the use of https required for this site?
     *
     * @return boolean
     */
    public function isHttpsRequired()
    {
        return ! ($this->offsetExists('http') && $this->offsetGet('http'));
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
     * a unique staff login id for each user, instead of for each
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

    /**
     * @return bool True when there are multiple locales and translate.databasefields is set to 1
     */
    public function translateDatabaseFields()
    {
        return isset($this['multiLocale'], $this['translate'], $this['translate']['databasefields']) &&
            $this['multiLocale'] &&
            (1 == $this['translate']['databasefields']);
    }

    /**
     * Does this project use Csrf checks
     *
     * @return boolean
     */
    public function useCsrfCheck()
    {
        if (isset($this['security'], $this['security']['disableCsrf']) && (1 == $this['security']['disableCsrf'])) {
            return false;
        }
        return true;
    }
}
