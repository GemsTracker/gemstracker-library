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
 * @version    $Id$
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
 * @subpackage Mail
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Email_TemplateMailer
{
    const MAIL_NO_ENCRYPT = 0;
    const MAIL_SSL = 1;
    const MAIL_TLS = 2;

    /**
     * @var GemsEscort $escort
     */
    protected $escort;

    protected $messages = array();

    private $_changeDate;
    private $_mailSubject;
    private $_mailKeys;
    private $_mailFields;
    private $_mailDate;

    private $_from = 'O';
    private $_tokens = array();
    private $_subject = null;
    private $_body = null;
    private $_method = 'M';
    private $_tokenData = array();

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

    public function checkTransport($from)
    {
        static $mailServers = array();

        if (!array_key_exists($from, $mailServers)) {
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
                $mailServers[$from] = null;
            }
        }

        return $mailServers[$from];
    }

    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Returns true if the "email.bounce" setting exists in the project
     * configuration and is true
     * @return boolean
     */
    public function bounceCheck()
    {
        return isset($this->escort->project->email['bounce']) && $this->escort->project->email['bounce'];
    }

    /**
     * Replaces fields with their values
     * @param  string $value
     * @return string
     */
    public function applyFields($value)
    {
        if (! $this->_mailFields) {
            $this->getTokenMailFields();
        }
        if (! $this->_mailKeys) {
            $this->_mailKeys = array_keys($this->_mailFields);
        }

        return str_replace($this->_mailKeys, $this->_mailFields, $value);
    }

    /**
     * Returns the name of the user mentioned in this token
     * in human-readable format
     *
     * @param  array $tokenData
     * @return string
     */
    public function getTokenName(array $tokenData = null)
    {
        $data[] = $tokenData['grs_first_name'];
        $data[] = $tokenData['grs_surname_prefix'];
        $data[] = $tokenData['grs_last_name'];

        $data = array_filter(array_map('trim', $data)); // Remove empties

        return implode(' ', $data);
    }

    /**
     * Sets verbose (noisy) operation
     * @param boolean $verbose
     */
    public function setVerbose($verbose)
    {
        $this->_verbose = $verbose;
    }

    /**
     * Sets sender (regular e-mail address) or one of:
     *    'O' - Uses the contact information of the selected organization
     *    'S' - Uses the site-wide contact information
     *    'U' - Uses the contact information of the currently logged in user
     *
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->_from = $from;
    }

    /**
     * Sets a list of tokens
     * @param string[] $tokens
     */
    public function setTokens(array $tokens)
    {
        $this->_tokens = $tokens;
    }

    /**
     * Sets the sending method to use
     *    'M' - Send multiple mails per respondent, one for each checked token.
     *    'O' - Send one mail per respondent, mark all checked tokens as send.
     *    'A' - Send one mail per respondent, mark only mailed tokens as send.
     *
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->_method = $method;
    }

    /**
     * Sets the subject of the mail
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;
    }

    /**
     * Sets the body of the mail
     * @param string $body
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }

    /**
     * Processes an array of token data and sends e-mails
     * @param  array $tokensData
     * @return boolean
     */
    public function process($tokensData)
    {
        if (isset($this->escort->project->email['block']) && $this->escort->project->email['block']) {
            $this->addMessage($this->escort->_('The sending of emails was blocked for this installation.'));
            return false;
        }

        switch ($this->_method) {
            case 'M':
                $mailAll = true;
                // $updateOne = false;
                break;

            case 'A':
                $mailAll   = false;
                $updateAll = false;
                break;

            default:
                $mailAll   = false;
                $updateAll = true;
                break;
        }

        $send = array();
        $scount = 0;
        $ucount = 0;

        foreach ($tokensData as $tokenData) {
            if (in_array($tokenData['gto_id_token'], $this->_tokens)) {
                if ($mailAll || (! isset($send[$tokenData['grs_email']]))) {

                    if ($message = $this->processMail($tokenData)) {
                        $this->addMessage($this->escort->_('Mail failed to send.'));
                        $this->addMessage($message);
                        return false;
                    }
                    $send[$tokenData['grs_email']] = true;
                    $scount++;
                    $ucount++;

                } elseif ($updateAll) {
                    $this->updateToken($tokenData);
                    $ucount++;
                }
            }
        }

        if ($scount) {
            $this->addMessage(sprintf($this->escort->_('Sent %d e-mails, updated %d tokens.'), $scount, $ucount));
        }

        return true;
    }

    /**
     * Sends a single mail
     * @param  array $tokenData
     * @return boolean|string
     */
    public function processMail(array $tokenData)
    {
        $to_name = $this->getTokenName($tokenData);
        $to      = $tokenData['grs_email'];

        switch ($this->_from) {
            case 'O':
                $from = $tokenData['gor_contact_email'];
                $from_name = $tokenData['gor_contact_name'] ? $tokenData['gor_contact_name'] : $tokenData['gor_name'];
                break;

            case 'S':
                $from = $this->escort->project->email['site'];
                $from_name = $this->escort->session->user_name;
                break;

            case 'U':
                $from = $this->escort->session->user_email;
                $from_name = $this->escort->project->name;
                break;

            default:
                $from = $this->_from;
                $from_name = null;
        }

        // BOUNCE CHECK
        if ($this->bounceCheck()) {
            $to_name = str_replace('@', ' at ', $to);
            $to      = $from;
        }

        $style = isset($tokenData['gor_style']) ? $tokenData['gor_style'] : null;

        if ($message = $this->sendMail($to, $to_name, $from, $from_name, $tokenData)) {
            return $message;
        } else {
            $this->updateToken($tokenData);
            return false;
        }
    }

    /**
     * Sends a single e-mail
     * @param  string $to
     * @param  string $to_name
     * @param  string $from
     * @param  string $from_name
     * @param  array $tokenData
     * @return boolean|string
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

        $this->setTokenData($tokenData);

        $style = isset($tokenData['gor_style']) ? $tokenData['gor_style'] : GEMS_PROJECT_NAME;

        $mail = new MUtil_Mail();
        $mail->setHtmlTemplateFile(APPLICATION_PATH . '/configs/email/' . $style . '.html');

        $mail->setFrom($from, $from_name);
        $mail->addTo($to, $to_name);
        if (isset($this->escort->project->email['bcc'])) {
            $mail->addBcc($this->escort->project->email['bcc']);
        }

        $subject = $this->applyFields($this->_subject);
        $body    = $this->applyFields($this->_body);

        $mail->setSubject($subject);
        $mail->setBodyBBCode($body);

        $this->_mailSubject = $subject;

        try {
            $mail->send($this->checkTransport($from));
            return false;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Updates a token
     * @param array $tokenData
     * @param string $subject
     */
    protected function updateToken(array $tokenData, $subject = null)
    {
        if (null === $subject) {
            $subject = $this->_mailSubject;
        } else {
            $this->_mailSubject = $subject;
        }

        if (null === $this->_changeDate) {
            $this->_changeDate = new Zend_Db_Expr('CURRENT_TIMESTAMP');
        }

        $db  = $this->escort->db;
        $uid = $this->escort->session->user_id;

        $tdata['gto_mail_sent_date'] = $this->_mailDate;

        $db->update('gems__tokens', $tdata, $db->quoteInto('gto_id_token = ?', $tokenData['gto_id_token']));

        $cdata['grco_id_to']        = $tokenData['grs_id_user'];
        $cdata['grco_id_by']        = $uid;
        $cdata['grco_organization'] = $tokenData['gor_id_organization'];
        $cdata['grco_id_token']     = $tokenData['gto_id_token'];
        $cdata['grco_method']       = 'email';
        $cdata['grco_topic']        = substr($subject, 0, 120);
        $cdata['grco_address']      = substr($tokenData['grs_email'], 0, 120);
        $cdata['grco_changed']      = $this->_changeDate;
        $cdata['grco_changed_by']   = $uid;
        $cdata['grco_created']      = $this->_changeDate;
        $cdata['grco_created_by']   = $uid;

        $db->insert('gems__respondent_communications', $cdata);
    }

    public function getTokenMailFields()
    {
        if (! $this->_mailFields) {
            $this->_mailFields = $this->escort->tokenMailFields($this->_tokenData);
        }

        return $this->_mailFields;
    }

    public function setTokenData(array $tokenData)
    {
        $this->_tokenData  = $tokenData;
        $this->_mailFields = null;
        return $this;
    }

    public function setTokenMailFields(array $tokenData = null)
    {
        if (null === $tokenData) {
            $tokenData = $this->getTokenData();
        } else {
            $this->setTokenData($tokenData);
        }
        if (! $this->_mailFields) {
            $this->_mailFields = $this->escort->tokenMailFields($tokenData);
        }

        return $this->_mailFields;
    }
}
