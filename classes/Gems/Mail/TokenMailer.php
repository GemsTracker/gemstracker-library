<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TokenMailer.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Mail_TokenMailer extends Gems_Mail_RespondentMailer
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
	protected $db;
	/**
	 *
	 * @var Gems_Loader;
	 */
	protected $loader;

    /**
     *
     * @var Gems_Tracker_Token
     */
	protected $token;
	protected $tokenIdentifier;

    /**
     *
     * @var Zend_Translate $translate
     */
	protected $translate;

    /**
     *
     * @var Gems_User;
     */
    protected $user;


	public function __construct($tokenIdentifier)
	{
		$this->tokenIdentifier = $tokenIdentifier;
	}

    protected function afterMail()
    {
        $this->updateToken();

        $this->logRespondentCommunication();
    }

	public function afterRegistry()
	{
        if ($this->tokenIdentifier) {
            $this->token = $this->loader->getTracker()->getToken($this->tokenIdentifier);
            if ($this->token->exists) {
                $this->patientId = $this->token->getPatientNumber();
                $this->organizationId = $this->token->getOrganizationId();
            }
        } else {
            $this->loadDefault();
        }

		parent::afterRegistry();
	}

    public function getDataLoaded()
    {
        if ($this->token) {
            if (parent::getDataLoaded()) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->addMessage($this->translate->_('Token not found'));
            return false;
        }
    }

    protected function getDefaultToken()
    {
        $model = $this->loader->getTracker()->getTokenModel();

        $organizationId = $this->loader->getCurrentUser()->getCurrentOrganizationId();

        $filter['gto_id_organization'] = $organizationId;
        $filter[] = 'grs_email IS NOT NULL';

        // Without sorting we get the fastest load times
        $sort = false;

        $tokenData = $model->loadFirst($filter, $sort);

        if (! $tokenData) {
            // Well then just try to get any token
            $tokenData = $model->loadFirst(false, $sort);
            if (! $tokenData) {
                //No tokens, just add an empty array and hope we get no notices later
                $tokenData = $model->loadNew();
            }
        }
        return $tokenData;
    }

    /**
     * Get specific data set in the mailer
     * @return Array
     */
    public function getPresetTargetData()
    {
        $targetData = parent::getPresetTargetData();
        if ($this->token) {
            $targetData['track']        = $this->token->getTrackName();
            $targetData['round']        = $this->token->getRoundDescription();
            $targetData['survey']       = $this->token->getSurvey()->getName();
            $targetData['last_contact'] = $this->token->getMailSentDate();
        }
        return $targetData;
    }

    protected function loadDefault()
    {
        $this->tokenIdentifier = $tokenData = $this->getDefaultToken();
        if (!empty($tokenData['gto_id_organization'])) {
            $this->organizationId = $tokenData['gto_id_organization'];
            $this->patientId = $tokenData['gr2o_patient_nr'];
            $this->respondent = $this->loader->getRespondent($this->patientId, $this->organizationId);
        }

        if ($tokenData['gto_id_token']) {
            $this->token = $this->loader->getTracker()->getToken($this->tokenIdentifier);
        }
    }

	/**
     * Get the respondent mailfields and add them
     */
    protected function loadMailFields()
    {
        parent::loadMailFields();
        $this->addMailFields($this->tokenMailFields());
    }

    /**
     * Log the communication for the respondent.
     */
    protected function logRespondentCommunication()
    {
        $currentUserId                = $this->loader->getCurrentUser()->getUserId();
        $changeDate                   = new MUtil_Db_Expr_CurrentTimestamp();

        $logData['grco_id_to']        = $this->respondent->getId();
        $logData['grco_id_by']        = $currentUserId;
        $logData['grco_organization'] = $this->organizationId;
        $logData['grco_id_token']     = $this->token->getTokenId();

        $logData['grco_method']       = 'email';
        $logData['grco_topic']        = substr($this->subject, 0, 120);

        $to = array();
        foreach($this->to as $name=> $address) {
            $to[] = $name . '<'.$address.'>';
        }

        $logData['grco_address']      = substr(join(',', $to), 0, 120);
        $logData['grco_sender']       = substr($this->from, 0, 120);

        $logData['grco_id_message']   = $this->templateId ? $this->templateId : null;

        $logData['grco_changed']      = $changeDate;
        $logData['grco_changed_by']   = $currentUserId;
        $logData['grco_created']      = $changeDate;
        $logData['grco_created_by']   = $currentUserId;

        $this->db->insert('gems__log_respondent_communications', $logData);
    }

	/**
     * Returns an array of {field_names} => values for this token for
     * use in an e-mail tamplate.
     *
     * @param array $tokenData
     * @return array
     */
    public function tokenMailFields()
    {
        if ($this->token) {
            $locale = $this->respondent->getLanguage();
            $survey = $this->token->getSurvey();
            // Count todo
            $tSelect = $this->loader->getTracker()->getTokenSelect(array(
                'all'   => 'COUNT(*)',
                'track' => $this->db->quoteInto(
                        'SUM(CASE WHEN gto_id_respondent_track = ? THEN 1 ELSE 0 END)',
                        $this->token->getRespondentTrackId())
                ));
            $tSelect->andSurveys(array())
                ->forRespondent($this->token->getRespondentId(), $this->organizationId)
                ->forGroupId($survey->getGroupId())
                ->onlyValid();
            $todo = $tSelect->fetchRow();

            // Set the basic fields

            $result['round']                   = $this->token->getRoundDescription();

            $organizationLoginUrl = $this->organization->getLoginUrl();

            $result['site_ask_url']            = $organizationLoginUrl . '/ask/';
            // Url's
            $url      = $organizationLoginUrl . '/ask/forward/' . MUtil_Model::REQUEST_ID . '/';
            $url      .= $this->token->getTokenId();
            $urlInput = $result['site_ask_url'] . 'index/' . MUtil_Model::REQUEST_ID . '/' . $this->token->getTokenId();

            $result['survey']           = $survey->getName();

            $result['todo_all']         = sprintf($this->translate->plural('%d survey', '%d surveys', $todo['all'], $locale), $todo['all']);
            $result['todo_all_count']   = $todo['all'];
            $result['todo_track']       = sprintf($this->translate->plural('%d survey', '%d surveys', $todo['track'], $locale), $todo['track']);
            $result['todo_track_count'] = $todo['track'];

            $result['token']            = strtoupper($this->token->getTokenId());
            $result['token_from']       = MUtil_Date::format($this->token->getValidFrom(), Zend_Date::DATE_LONG, 'yyyy-MM-dd', $locale);

            $result['token_link']       = '[url=' . $url . ']' . $survey->getName() . '[/url]';

            $result['token_until']      = MUtil_Date::format($this->token->getValidUntil(), Zend_Date::DATE_LONG, 'yyyy-MM-dd', $locale);
            $result['token_url']        = $url;
            $result['token_url_input']  = $urlInput;

            $result['track']            = $this->token->getTrackName();

            // Add the code fields
            $join = $this->db->quoteInto('gtf_id_field = gr2t2f_id_field AND gr2t2f_id_respondent_track = ?', $this->token->getRespondentTrackId());
            $select = $this->db->select();
            $select->from('gems__track_fields', array(new Zend_Db_Expr("CONCAT('track.', gtf_field_code)")))
                    ->joinLeft('gems__respondent2track2field', $join, array('gr2t2f_value'))
                    ->distinct()
                    ->where('gtf_field_code IS NOT NULL')
                    ->order('gtf_field_code');
            $codes = $this->db->fetchPairs($select);

            $result = $result + $codes;
        } else {
            $result['round']            = '';
            $result['site_ask_url']     = '';
            $result['survey']           = '';
            $result['todo_all']         = '';
            $result['todo_all_count']   = '';
            $result['todo_track']       = '';
            $result['todo_track_count'] = '';
            $result['token']            = '';
            $result['token_from']       = '';
            $result['token_link']       = '';
            $result['token_until']      = '';
            $result['token_url']        = '';
            $result['token_url_input']  = '';
            $result['track']            = '';
        }

        return $result;
    }

    /**
     * Update the token data when a Mail has been sent.
     * @param  integer $tokenId TokenId to update. If none is supplied, use the current token
     */
    public function updateToken($tokenId=false)
    {
        if (!$tokenId) {
            $tokenId = $this->token->getTokenId();
        }
        $tokenData['gto_mail_sent_num'] = new Zend_Db_Expr('gto_mail_sent_num + 1');
        $tokenData['gto_mail_sent_date'] = MUtil_Date::format(new Zend_Date(), 'yyyy-MM-dd');

        $this->db->update('gems__tokens', $tokenData, $this->db->quoteInto('gto_id_token = ?', $tokenId));
    }
}