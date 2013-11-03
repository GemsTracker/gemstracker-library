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
 *
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Performs bulk-mail action, can be called from a cronjob
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_CronAction extends Gems_Controller_Action
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     *
     * @var Gems_Menu
     */
    public $menu;

    /**
     *
     * @var Zend_Session_Namespace
     */
    public $session;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * Otherwise call $this->initHtml()
     *
     * @var boolean $useHtmlView
     */
    public $useHtmlView = true;

    /**
     *
     * @var Gems_Util
     */
    public $util;

    /**
     * Action that switches the cron job lock on or off.
     */
    public function cronLockAction()
    {
        // Switch lock
        $this->util->getCronJobLock()->reverse();

        // Redirect
        $request = $this->getRequest();
        $this->_reroute($this->menu->getCurrentParent()->toRouteUrl());
    }

    /**
     * Loads an e-mail template
     *
     * @param integer|null $templateId
     */
    protected function getTemplate($templateId)
    {
        return $this->db->fetchRow('SELECT * FROM gems__mail_templates WHERE gmt_id_message = ?', $templateId);
    }

    /**
     * Returns the login name belonging to this user.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserLogin($userId)
    {
        return $this->db->fetchOne("SELECT gsf_login FROM gems__staff WHERE gsf_id_user = ?", $userId);
    }

    public function indexAction()
    {
        $this->initHtml();
        if ($this->util->getCronJobLock()->isLocked()) {
            $this->html->append($this->_('Cron jobs turned off.'));
        } else {
            $this->commJob();
        }
    }

    public function mailJob()
    {
        $userLoader = $this->loader->getUserLoader();
        $startUser  = $userLoader->getCurrentUser();
        $user       = $startUser;

        // Check for unprocessed tokens
        $this->loader->getTracker()->processCompletedTokens(null, $startUser->getUserId());

        $model  = $this->loader->getTracker()->getTokenModel();
        $mailer = new Gems_Email_TemplateMailer($this->escort);
        $mailer->continueOnError = true;

        // $mailer->setDefaultTransport(new MUtil_Mail_Transport_EchoLog());
        $sql = "SELECT *
            FROM gems__mail_jobs
            WHERE gmj_active = 1
            ORDER BY CASE WHEN gmj_id_survey IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gmj_id_track IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gmj_id_organization IS NULL THEN 1 ELSE 0 END";

        $jobs = $this->db->fetchAll($sql);

        if ($jobs) {
            foreach ($jobs as $job) {
                if ($user->getUserId() != $job['gmj_id_user_as']) {
                    $user = $userLoader->getUserByStaffId($job['gmj_id_user_as']);
                }

                if ($user->isActive()) {
                    if (! $user->isCurrentUser()) {
                        $user->setAsCurrentUser();
                    }

                    $filter = $this->loader->getUtil()->getDbLookup()->getFilterForMailJob($job);

                    $tokensData = $model->load($filter);

                    if (count($tokensData)) {
                        $mailer->setMethod($job['gmj_process_method']);
                        if ($job['gmj_from_method'] == 'F') {
                            $mailer->setFrom($job['gmj_from_fixed']);
                        } else {
                            $mailer->setFrom($job['gmj_from_method']);
                        }

                        $templateData = $this->getTemplate($job['gmj_id_message']);
                        $mailer->setSubject($templateData['gmt_subject']);
                        $mailer->setBody($templateData['gmt_body']);

                        $mailer->setTokens(MUtil_Ra::column('gto_id_token', $tokensData));
                        $mailer->process($tokensData);
                    }
                    $tokensData = null;
                }
            }
        }

        $msg = $mailer->getMessages();
        if (! $msg) {
            $msg[] = $this->_('No mails sent.');
        }
        if ($mailer->bounceCheck()) {
            array_unshift($msg, $this->_('On this test system all mail will be delivered to the from address.'));
        }

        $this->addMessage($msg);

        if (! $startUser->isCurrentUser()) {
            $startUser->setAsCurrentUser();
        }
    }

    public function commJob()
    {
        $userLoader = $this->loader->getUserLoader();
        $startUser  = $userLoader->getCurrentUser();
        $user       = $startUser;

        // Check for unprocessed tokens
        $this->loader->getTracker()->processCompletedTokens(null, $startUser->getUserId());

        $model  = $this->loader->getTracker()->getTokenModel();
        
        $sql = "SELECT *
            FROM gems__comm_jobs
            WHERE gcj_active = 1
            ORDER BY CASE WHEN gcj_id_survey IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_track IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_organization IS NULL THEN 1 ELSE 0 END";

        $jobs = $this->db->fetchAll($sql);

        if ($jobs) {
            foreach ($jobs as $job) {
                if ($user->getUserId() != $job['gcj_id_user_as']) {
                    $user = $userLoader->getUserByStaffId($job['gcj_id_user_as']);
                }

                if ($user->isActive()) {
                    if (! $user->isCurrentUser()) {
                        $user->setAsCurrentUser();
                    }

                    $filter = $this->loader->getUtil()->getDbLookup()->getFilterForMailJob($job);

                    $multipleTokensData = $model->load($filter);

                    if (count($tokensData)) {

                        $mails = 0;
                        $updates = 0;
                        $mailSent = false;

                        foreach($multipleTokenData as $tokenData) {
                            $mailer = $this->loader->getMailLoader()->getMailer('token', $tokenData);

                            if ($job['gcj_from_method'] == 'O') {
                                $organization  = $this->mailer->getOrganization();
                                $from = $organization->getName() . ' <' . $organization->getEmail() . '>';
                                $mailer->setFrom($from);
                            } elseif ($job['gcj_from_method'] == 'U') {
                                $from = $user->getFullName() . ' <' . $user->getEmailAddress() . '>';
                            } elseif ($job['gcj_from_method'] == 'F') {
                                $mailer->setFrom($job['gcj_from_fixed']);
                            }

                            if ($job['gcj_process_method'] == 'M') {
                                $mailer->setTemplate($job['gcj_id_message']);
                                $mailer->send();

                                $mails++;
                                $updates++;
                            } elseif (!$mailSent) {
                                $mailer->setTemplate($job['gcj_id_message']);
                                $mailer->send();
                                $mailSent = true;
                                $mails++;
                                $updates++;
                                if ($job['gcj_process_method'] == 'A') {
                                    break;
                                }
                            } elseif ($job['gcj_process_method'] == 'O') {
                                $mailer->updateToken();
                                $updates++;
                            }
                        }
        
                        $this->addMessage(sprintf($this->_('Sent %d e-mails, updated %d tokens.'), $mails, $updates));
                    }
                    $tokensData = null;
                }
            }
        }

        if (! $startUser->isCurrentUser()) {
            $startUser->setAsCurrentUser();
        }
    }
}