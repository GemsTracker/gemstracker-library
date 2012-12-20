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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @version    $Id: TemplateMailer.php 792 2012-06-27 11:59:17Z matijsdejong $
 * @package    Gems
 * @subpackage Email
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Mailer utility class
 *
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @package    Gems
 * @subpackage Email
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Email_Mailer
{
    const MAIL_NO_ENCRYPT = 0;
    const MAIL_SSL = 1;
    const MAIL_TLS = 2;

    /**
     * Should the mailer continue sending mails, even when it encounters errors?
     *
     * @var boolean
     */
    public $continueOnError = false;

    /**
     *
     * @var Zend_Mail_Transport
     */
    protected $defaultTransport = null;

    /**
     * @var GemsEscort $escort
     */
    protected $escort;

    /**
     * Feedback messages for this action.
     *
     * @var array of string
     */
    protected $messages = array();

    private $_changeDate;
    private $_mailSubject;
    private $_mailDate;

    private $_subject = null;
    private $_body = null;
    private $_templateId = null; // Not used for lookup

    private $_verbose = false;

    /**
     * Constructs a new Gems_Email_TemplateMailer
     * @param GemsEscort $escort
     */
    public function __construct(GemsEscort $escort)
    {
        $this->escort = $escort;
        $this->_mailDate = MUtil_Date::format(new Zend_Date(), 'yyyy-MM-dd');
    }

    protected function addMessage($message)
    {
        $this->messages[] = $message;
        return $this;
    }

    /**
     * Returns true if the "email.bounce" setting exists in the project
     * configuration and is true
     * @return boolean
     */
    public function bounceCheck()
    {
        return $this->escort->project->getEmailBounce();
    }

    /**
     * Returns Zend_Mail_Transport_Abstract when something else than the default mail protocol should be used.
     *
     * @staticvar array $mailServers
     * @param email address $from
     * @return Zend_Mail_Transport_Abstract or null
     */
    public function checkTransport($from)
    {
        static $mailServers = array();

        if (! array_key_exists($from, $mailServers)) {
            $sql = 'SELECT * FROM gems__mail_servers WHERE ? LIKE gms_from ORDER BY LENGTH(gms_from) DESC LIMIT 1';

            // Always set cache, se we know when not to check for this row.
            $serverData = $this->escort->db->fetchRow($sql, $from);

            // MUtil_Echo::track($serverData);

            if (isset($serverData['gms_server'])) {
                $options = array();
                if (isset($serverData['gms_user'], $serverData['gms_password'])) {
                    $options['auth'] = 'login';
                    $options['username'] = $serverData['gms_user'];
                    $options['password'] = $serverData['gms_password'];
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

                $mailServers[$from] = new Zend_Mail_Transport_Smtp($serverData['gms_server'], $options);
            } else {
                $mailServers[$from] = $this->defaultTransport;
            }
        }

        return $mailServers[$from];
    }

    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Logs the communication if needed
     *
     * @param string $to Optional, if available the communication is logged.
     * @param string $from Optional
     * @param array $tokenData Optional, array containing grs_id_user, gor_id_organization, gto_id_token
     */
    protected function logCommunication($to = null, $from = null, $tokenData = array() )
    {
        if (null === $this->_changeDate) {
            $this->_changeDate = new MUtil_Db_Expr_CurrentTimestamp();
        }

        $db  = $this->escort->db;
        $uid = $this->escort->getCurrentUserId();

        if ($to) {
            $cdata['grco_id_to']        = array_key_exists('grs_id_user', $tokenData) ? $tokenData['grs_id_user'] : 0 ;
            $cdata['grco_id_by']        = $uid;
            $cdata['grco_organization'] = array_key_exists('gor_id_organization', $tokenData) ? $tokenData['gor_id_organization'] : 0;
            $cdata['grco_id_token']     = array_key_exists('gto_id_token', $tokenData) ? $tokenData['gto_id_token'] : null ;

            $cdata['grco_method']       = 'email';
            $cdata['grco_topic']        = substr($this->_mailSubject, 0, 120);
            $cdata['grco_address']      = substr($to, 0, 120);
            $cdata['grco_sender']       = substr($from, 0, 120);

            $cdata['grco_id_message']   = $this->_templateId ? $this->_templateId : null;

            $cdata['grco_changed']      = $this->_changeDate;
            $cdata['grco_changed_by']   = $uid;
            $cdata['grco_created']      = $this->_changeDate;
            $cdata['grco_created_by']   = $uid;

            $db->insert('gems__log_respondent_communications', $cdata);
        }
    }

    /**
     * Sends a single e-mail
     * @param  string $to
     * @param  string $to_name
     * @param  string $from
     * @param  string $from_name
     * @param  array $tokenData
     * @return boolean|string String = error message from protocol.
     */
    public function sendMail($to, $to_name, $from, $from_name, array $tokenData)
    {
        if (empty($from) || empty($to)) {
            return "Need a sender and a recipient to continue";
        }

        if ($this->_verbose) {
            MUtil_Echo::r($to, $to_name);
            MUtil_Echo::r($from, $from_name);
        }


        // If bounce is active, the Gems_Mail will take care of resetting the to field
        if (!$this->bounceCheck()) {
            $validate = new Zend_Validate_EmailAddress();

            if (!$validate->isValid($to)) {
                return sprintf($this->escort->_("Invalid e-mail address '%s'."), $to);
            }
        }

        $mail = new Gems_Mail();

        $mail->setFrom($from, $from_name);
        $mail->addTo($to, $to_name);
        if (isset($this->escort->project->email['bcc'])) {
            $mail->addBcc($this->escort->project->email['bcc']);
        }

        $subject = $this->_subject;
        $body    = $this->_body;

        $mail->setSubject($subject);
        $mail->setBodyBBCode($body);

        $this->_mailSubject = $subject;

        try {
            $mail->send($this->checkTransport($from));
            $result = false;

            // communication successful, now log it
            $this->logCommunication($to, $from, $tokenData);

        } catch (Exception $e) {
            $result = $e->getMessage();

            // Log to error file
            $this->escort->logger->logError($e, $this->escort->request);
        }

        return $result;
    }

    /**
     * Sets the body of the mail
     * @param string $body
     * @return Gems_Email_TemplateMailer (continuation pattern)
     */
    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    /**
     * Set a different default transport protocol.
     *
     * @param Zend_Mail_Transport_Abstract $transport
     * @return Gems_Email_TemplateMailer (continuation pattern)
     */
    public function setDefaultTransport(Zend_Mail_Transport_Abstract $transport)
    {
        $this->defaultTransport = $transport;
        return $this;
    }

    /**
     * Sets the subject of the mail
     * @param string $subject
     * @return Gems_Email_TemplateMailer (continuation pattern)
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }

    public function setTemplateId($templatedId)
    {
        $this->_templateId = $templatedId;
        return $this;
    }

    /**
     * Sets verbose (noisy) operation
     *
     * @param boolean $verbose
     * @return Gems_Email_TemplateMailer (continuation pattern)
     */
    public function setVerbose($verbose)
    {
        $this->_verbose = $verbose;
        return $this;
    }
}