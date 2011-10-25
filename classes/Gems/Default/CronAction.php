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
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @package Gems
 * @subpackage Default
 */

/**
 * Performs bulk-mail action, can be called from a cronjob
 *
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_CronAction extends MUtil_Controller_Action
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * Standard filter that must be true for every token query.
     *
     * @var array
     */
    protected $defaultFilter = array(
        	'can_email'           => 1,
            'gtr_active'          => 1,
            'gsu_active'          => 1,
            'grc_success'         => 1,
        	'gto_completion_time' => NULL,
        	'gto_valid_from <= CURRENT_DATE',
            '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)'
        );

    /**
     *
     * @var GemsEscort
     */
    public $escort;

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
            $this->mailJob();
        }
    }

    public function mailJob()
    {
        // Test: update `gems__tokens` set `gto_mail_sent_date` = null where `gto_mail_sent_date` > '2011-10-23'

        $currentUser = isset($this->session->user_login) ? $this->session->user_login : null;

        $model  = $this->loader->getTracker()->getTokenModel();
        $mailer = new Gems_Email_TemplateMailer($this->escort);
        // $mailer->setDefaultTransport(new MUtil_Mail_Transport_EchoLog());

        $jobs = $this->db->fetchAll("SELECT * FROM gems__mail_jobs WHERE gmj_active = 1");

        if ($jobs) {
            foreach ($jobs as $job) {
                $this->escort->loadLoginInfo($this->getUserLogin($job['gmj_id_user_as']));

                // Set up filter
                $filter = $this->defaultFilter;
                if ($job['gmj_filter_mode'] == 'R') {
                    $filter[] = 'gto_mail_sent_date <= DATE_SUB(CURRENT_DATE, INTERVAL ' . $job['gmj_filter_days_between'] . ' DAY)';
                } else {
                    $filter['gto_mail_sent_date'] = NULL;
                }
                if ($job['gmj_id_organization']) {
                    $filter['gto_id_organization'] = $job['gmj_id_organization'];
                }
                if ($job['gmj_id_track']) {
                    $filter['gto_id_track'] = $job['gmj_id_track'];
                }
                if ($job['gmj_id_survey']) {
                    $filter['gto_id_survey'] = $job['gmj_id_survey'];
                }

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

                Gems_Auth::getInstance()->clearIdentity();
                $this->escort->session->unsetAll();
            }
        }

        $msg = $mailer->getMessages();
        if (! $msg) {
            $msg[] = $this->_('No mails sent');
        }

        $this->html->append($msg);

        if ($currentUser) {
            $this->escort->loadLoginInfo($currentUser);
        } else {
            $this->escort->afterLogout();
        }

        /*
        if (isset($this->project->email['automatic'])) {
            $batches = $this->project->email['automatic'];
            $numBatches = count($batches['mode']);

            for ($i = 0; $i < $numBatches; $i++) {
                $this->_organizationId = $batches['organization'][$i];

                if (isset($batches['days'][$i])) {
                    $this->_intervalDays = $batches['days'][$i];
                }

                $this->escort->loadLoginInfo($batches['user'][$i]);

                $model->setFilter($this->getFilter($batches['mode'][$i]));

                $tokensData = $model->load();

                if (count($tokensData)) {
                    $tokens = array();

                    foreach ($tokensData as $tokenData) {
                        $tokens[] = $tokenData['gto_id_token'];
                    }

                    $templateData = $this->getTemplate($batches['template'][$i]);
                    $mailer->setSubject($templateData['gmt_subject']);
                    $mailer->setBody($templateData['gmt_body']);
                    $mailer->setMethod($batches['method'][$i]);
                    $mailer->setFrom($batches['from'][$i]);
                    $mailer->setTokens($tokens);

                    $mailer->process($tokensData);
                }

                Gems_Auth::getInstance()->clearIdentity();
                $this->escort->session->unsetAll();
            }
        }
        // */
    }
}