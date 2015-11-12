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
class Gems_Default_CronAction extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

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
     * @var \Gems_Util
     */
    public $util;

    /**
     * Perform automatic job mail
     */
    public function commJob()
    {
        /*
        \Zend_Mail::setDefaultTransport(new \Zend_Mail_Transport_File(array(
            'callback' => function ($transport) {
                // throw new \Zend_Mail_Transport_Exception('Invalid e-mail address');
                return $transport->recipients . '_' . time() . '_' . mt_rand() . '.tmp';
            },
            'path'     => GEMS_ROOT_DIR . '/var/sentmails'
        )));
        // */


        $dbLookup   = $this->util->getDbLookup();
        $mailLoader = $this->loader->getMailLoader();
        $tracker    = $this->loader->getTracker();
        $model      = $tracker->getTokenModel();

        // Fix for #680: token with the valid from the longest in the past should be the
        // used as first token and when multiple rounds start at the same date the
        // lowest round order should be used.
        $model->setSort(array('gto_valid_from' => SORT_ASC, 'gto_round_order' => SORT_ASC));

        // Check for unprocessed tokens
        $tracker->processCompletedTokens(null, $this->currentUser->getUserId());

        $sql = "SELECT *
            FROM gems__comm_jobs INNER JOIN
                gems__comm_templates ON gcj_id_message = gct_id_template
            WHERE gcj_active = 1
            ORDER BY CASE WHEN gcj_id_survey IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_round_description IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_track IS NULL THEN 1 ELSE 0 END,
                CASE WHEN gcj_id_organization IS NULL THEN 1 ELSE 0 END";

        $jobs = $this->db->fetchAll($sql);

        $mailed = false;
        if ($jobs) {
            foreach ($jobs as $job) {
                $sendByMail = $this->getUserEmail($job['gcj_id_user_as']);

                $filter = $dbLookup->getFilterForMailJob($job);

                $multipleTokensData = $model->load($filter);
                if (count($multipleTokensData)) {

                    $errors  = 0;
                    $mails   = 0;
                    $updates = 0;
                    $sentMailAddresses = array();

                    foreach($multipleTokensData as $tokenData) {
                        $mailer = $mailLoader->getMailer('token', $tokenData);
                        /* @var $mailer \Gems_Mail_TokenMailer */
                        $token = $mailer->getToken();
                        $email = $token->getEmail();
                        $respondentId = $token->getRespondent()->getId();

                        if (!empty($email)) {
                            if ($job['gcj_from_method'] == 'O') {
                                $organization  = $mailer->getOrganization();
                                $from = $organization->getEmail();//$organization->getName() . ' <' . $organization->getEmail() . '>';
                                $mailer->setFrom($from);
                            } elseif ($job['gcj_from_method'] == 'U') {
                                $from = $sendByMail;
                                $mailer->setFrom($from);
                            } elseif ($job['gcj_from_method'] == 'F') {
                                $mailer->setFrom($job['gcj_from_fixed']);
                            }
                            $mailer->setBy($sendByMail);

                            try {
                                if ($job['gcj_process_method'] == 'M') {
                                    $mailer->setTemplate($job['gcj_id_message']);
                                    $mailer->send();
                                    $mailed = true;

                                    $mails++;
                                    $updates++;
                                } elseif (!isset($sentMailAddresses[$respondentId][$email])) {
                                    $mailer->setTemplate($job['gcj_id_message']);
                                    $mailer->send();
                                    $mailed = true;

                                    $mails++;
                                    $updates++;
                                    $sentMailAddresses[$respondentId][$email] = true;

                                } elseif ($job['gcj_process_method'] == 'O') {
                                    $mailer->updateToken();
                                    $updates++;
                                }
                            } catch (\Zend_Mail_Exception $exception) {
                                $fields = $mailer->getMailFields(false);

                                $info = sprintf("Error mailing to %s respondent %s with email address %s.",
                                        $fields['organization'],
                                        $fields['full_name'],
                                        $fields['email']
                                        );

                                // Use a gems exception to pass extra information to the log
                                $gemsException = new \Gems_Exception($info, 0, $exception);
                                \Gems_Log::getLogger()->logError($gemsException);

                                $errors++;
                            }
                        }
                    }

                    $this->addMessage(sprintf(
                            $this->_('Sent %d e-mails with template %s, updated %d tokens.'),
                            $mails,
                            $job['gct_name'],
                            $updates
                            ));

                    if ($errors) {
                        $this->addMessage(sprintf(
                                $this->_('%d error(s) occurred while creating mails for template %s. Check error log for details.'),
                                $errors,
                                $job['gct_name']
                                ));
                    }
                }
                $tokensData = null;
            }
        }

        if (!$mailed) {
            $this->addMessage($this->_('No mails sent.'));
        }
    }

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
     * Returns the login name belonging to this user.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserLogin($userId)
    {
        return $this->db->fetchOne("SELECT gsf_login FROM gems__staff WHERE gsf_id_user = ?", $userId);
    }

    /**
     * Returns the Email belonging to this user.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserEmail($userId)
    {
        return $this->db->fetchOne("SELECT gsf_email FROM gems__staff WHERE gsf_id_user = ?", $userId);
    }

    /**
     * The general "do the jobs" action
     */
    public function indexAction()
    {
        $this->initHtml();
        if ($this->util->getCronJobLock()->isLocked()) {
            $this->html->append($this->_('Cron jobs turned off.'));
        } else {
            $this->commJob();
        }
    }
}