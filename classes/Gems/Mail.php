<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class Gems_Mail extends \MUtil_Mail
{
    const MAIL_NO_ENCRYPT = 0;
    const MAIL_SSL = 1;
    const MAIL_TLS = 2;

    /**
     * @var \GemsEscort
     */
    public $escort = null;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Mail character set
     *
     * For Gems we use utf-8 as default instead op iso-8859-1
     * @var string
     */
    protected $_charset = 'utf-8';

    protected static $mailServers = array();

    public function __construct($charset = null)
    {
        parent::__construct($charset);
    }

    /**
     * Adds To-header and recipient, $email can be an array, or a single string address
     *
     * @param string|array $email
     * @param string $name
     * @param boolean $bounce When true the e-mail is bounced to the from address, when omitted bounce is read from project settings
     * @return \Zend_Mail Provides fluent interface
     */
    public function addTo($email, $name = '', $bounce = null)
    {
        if (is_null($bounce)) {
            $bounce = $this->bounceCheck();
        }
        if ($bounce === true) {
            $name  = str_replace('@', ' at ', $email);
            if (is_array($email)) {
                $name = array_shift($name);
                if (count($email) > 1) {
                    $name .= ' & ' . (count($email)-1) . ' addresses';
                }
            }
            $email = $this->getFrom();
            if (! $email) {
                throw new \Gems_Exception_Coding('Adding bounce To address while From is not set.');
            }
        }
        return parent::addTo($email, $name);
    }

    /**
     * Returns true if the "email.bounce" setting exists in the project
     * configuration and is true
     * @return boolean
     */
    public function bounceCheck()
    {
        return $this->project->getEmailBounce();
    }

    /**
     * Returns \Zend_Mail_Transport_Abstract when something else than the default mail protocol should be used.
     *
     * @staticvar array $mailServers
     * @param email address $from
     * @return \Zend_Mail_Transport_Abstract or null
     */
    public function checkTransport($from)
    {
        if (! array_key_exists($from, self::$mailServers)) {
            $sql = 'SELECT * FROM gems__mail_servers WHERE ? LIKE gms_from ORDER BY LENGTH(gms_from) DESC LIMIT 1';

            // Always set cache, se we know when not to check for this row.
            $serverData = $this->db->fetchRow($sql, $from);

            // \MUtil_Echo::track($serverData);

            if (isset($serverData['gms_server'])) {
                $options = array();
                if (isset($serverData['gms_user'], $serverData['gms_password'])) {
                    $options['auth'] = 'login';
                    $options['username'] = $serverData['gms_user'];
                    $options['password'] = $this->project->decrypt(
                            $serverData['gms_password'],
                            $serverData['gms_encryption']
                            );
                }
                if (isset($serverData['gms_port'])) {
                    $options['port'] = $serverData['gms_port'];
                }
                if (isset($serverData['gms_ssl'])) {
                    switch ($serverData['gms_ssl']) {
                        case self::MAIL_SSL:
                            $options['ssl'] = 'ssl';
                            break;

                        case self::MAIL_TLS:
                            $options['ssl'] = 'tls';
                            break;

                        default:
                            // intentional fall through

                    }
                }

                self::$mailServers[$from] = new \Zend_Mail_Transport_Smtp($serverData['gms_server'], $options);
            } else {
                self::$mailServers[$from] = $this->getDefaultTransport();
            }
        }

        return self::$mailServers[$from];
    }
    
    /**
     * Returns the the current template
     *
     * @return string
     */
    public function getHtmlTemplate()
    {
        if (! $this->_htmlTemplate) {
            $this->setTemplateStyle();
        }

        return parent::getHtmlTemplate();
    }

    /**
     * Set the template using style as basis
     *
     * @param string $style
     * @return \MUtil_Mail (continuation pattern)
     */
    public function setTemplateStyle($style = null)
    {
        if (null == $style) {
            $style = GEMS_PROJECT_NAME;
        }
        $this->setHtmlTemplateFile(APPLICATION_PATH . '/configs/email/' . $style . '.html');

        return $this;
    }

    public function send($transport = null) {
        $from = $this->getFrom();
        if (empty($from)) {
            throw new \Gems_Exception('No sender email set!');
        }
        
        // Get the transport method when it was not set
        if (is_null($transport)) {
            $transport = $this->checkTransport($this->getFrom());
        }

        parent::send($transport);
    }
}
