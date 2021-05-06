<?php

/**
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
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
class Gems_Mail_TokenMailer extends \Gems_Mail_RespondentMailer
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
	protected $db;
	/**
	 *
	 * @var \Gems_Loader;
	 */
	protected $loader;

    /**
     *
     * @var \Gems_Tracker_Token
     */
	protected $token;

    /**
     *
     * @var string
     */
	protected $tokenIdentifier;

    /**
     *
     * @param string $tokenIdentifier
     */
	public function __construct($tokenIdentifier)
	{
		$this->tokenIdentifier = $tokenIdentifier;
	}

    /**
     * Called after a mail has been sent
     */
    protected function afterMail()
    {
        $this->updateToken();

        $this->logRespondentCommunication();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
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

        if ($this->token && $this->token->hasRelation() && $relation = $this->token->getRelation()) {
            // If we have a token with a relation, remove the respondent and use relation in to field
            $this->to = array();
            $this->addTo($relation->getEmail(), $relation->getName());
        }
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
            $this->addMessage($this->_('Token not found'));
            return false;
        }
    }

    protected function getDefaultToken()
    {
        $model = $this->loader->getTracker()->getTokenModel();

        $organizationId = $this->loader->getCurrentUser()->getCurrentOrganizationId();

        $filter['gto_id_organization'] = $organizationId;
        $filter[] = 'gr2o_email IS NOT NULL';

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

    /**
     * Get the token object used in this mailer
     *
     * @return \Gems_Tracker_Token
     * @throws \Gems_Exception_Coding
     */
    public function getToken()
    {
        if ($this->token instanceof \Gems_Tracker_Token) {
            return $this->token;
        } else {
            throw new \Gems_Exception_Coding('Token not loaded');
        }
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

        $this->mailFields = $this->tokenMailFields() + $this->mailFields;   // Prefer our fields over the ones from the super class
    }

    /**
     * Log the communication for the respondent.
     */
    protected function logRespondentCommunication()
    {
        $currentUserId                = $this->loader->getCurrentUser()->getUserId();
        $changeDate                   = new \MUtil_Db_Expr_CurrentTimestamp();

        $logData['grco_id_to']        = $this->respondent->getId();

        if (! is_int($this->by)) {
            $this->by = $currentUserId;
        }

        $logData['grco_id_by']        = $this->by;
        $logData['grco_organization'] = $this->organizationId;
        $logData['grco_id_token']     = $this->token->getTokenId();

        $logData['grco_method']       = 'email';
        $logData['grco_topic']        = substr($this->applyFields($this->subject), 0, 120);

        $to = array();
        foreach($this->to as $name=> $address) {
            $to[] = $name . '<'.$address.'>';
        }

        $logData['grco_address']      = substr(join(',', $to), 0, 120);
        $logData['grco_sender']       = substr($this->from, 0, 120);

        $logData['grco_id_message']   = $this->templateId ? $this->templateId : null;
        $logData['grco_id_job']       = $this->mailjob ? $this->mailjob : null;

        $logData['grco_changed']      = $changeDate;
        $logData['grco_changed_by']   = $this->by;
        $logData['grco_created']      = $changeDate;
        $logData['grco_created_by']   = $this->by;

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
            $url      = $organizationLoginUrl . '/ask/forward/' . \MUtil_Model::REQUEST_ID . '/';
            $url      .= $this->token->getTokenId();
            $urlInput = $result['site_ask_url'] . 'index/' . \MUtil_Model::REQUEST_ID . '/' . $this->token->getTokenId();

            $result['survey']           = $survey->getExternalName();

            $result['todo_all']         = sprintf($this->plural('%d survey', '%d surveys', $todo['all']), $todo['all']);
            $result['todo_all_count']   = $todo['all'];
            $result['todo_track']       = sprintf($this->plural('%d survey', '%d surveys', $todo['track']), $todo['track']);
            $result['todo_track_count'] = $todo['track'];

            $result['token']            = strtoupper($this->token->getTokenId());
            $result['token_from']       = \MUtil_Date::format($this->token->getValidFrom(), \Zend_Date::DATE_LONG, 'yyyy-MM-dd');

            $result['token_link']       = '[url=' . $url . ']' . $survey->getExternalName() . '[/url]';

            $result['token_until']      = \MUtil_Date::format($this->token->getValidUntil(), \Zend_Date::DATE_LONG, 'yyyy-MM-dd');
            $result['token_url']        = $url;
            $result['token_url_input']  = $urlInput;

            $result['track']            = $this->token->getTrackName();

            // Add the code fields
            $codes  = $this->token->getRespondentTrack()->getCodeFields();
            foreach ($codes as $code => $data) {
                $key = 'track.' . $code;
                if (is_array($data)) {
                    $data = implode(' ', $data);
                }
                $result[$key] = $data;
            }

            if ($this->token->hasRelation()) {
                $allFields = $this->getMailFields(false);
                // Set about to patient name
                $result['relation_about'] = $allFields['name'];
                $result['relation_about_first_name'] = $allFields['first_name'];
                $result['relation_about_full_name'] = $allFields['full_name'];
                $result['relation_about_greeting'] = $allFields['greeting'];
                $result['relation_about_last_name'] = $allFields['last_name'];
                $result['relation_field_name'] = $this->token->getRelationFieldName();

                if ($relation = $this->token->getRelation()) {
                    // Now update all respondent fields to be of the relation
                    $result['name']       = $relation->getName();
                    $result['first_name'] = $relation->getFirstName();
                    $result['last_name']  = $relation->getLastName();
                    $result['full_name']  = $relation->getHello();
                    $result['greeting']   = $relation->getGreeting();
                    $result['to']         = $relation->getEmail();
                } else {
                    $result['name']       = $this->_('Undefined relation');
                    $result['first_name'] = '';
                    $result['last_name']  = '';
                    $result['full_name']  = '';
                    $result['greeting']   = '';
                    $result['to']         = '';
                }
            } else {
                $result['relation_about'] = $this->_('yourself');
                $result['relation_about_first_name'] = '';
                $result['relation_about_full_name'] = '';
                $result['relation_about_greeting'] = '';
                $result['relation_about_last_name'] = '';
                $result['relation_field_name'] = '';
            }

        } else {
            $result['round']                     = '';
            $result['site_ask_url']              = '';
            $result['survey']                    = '';
            $result['todo_all']                  = '';
            $result['todo_all_count']            = '';
            $result['todo_track']                = '';
            $result['todo_track_count']          = '';
            $result['token']                     = '';
            $result['token_from']                = '';
            $result['token_link']                = '';
            $result['token_until']               = '';
            $result['token_url']                 = '';
            $result['token_url_input']           = '';
            $result['track']                     = '';
            $result['relation_about']            = $this->_('yourself');
            $result['relation_about_first_name'] = '';
            $result['relation_about_full_name']  = '';
            $result['relation_about_greeting']   = '';
            $result['relation_about_last_name']  = '';
            $result['relation_field_name']       = '';
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
            $this->token->setMessageSent();
            return;
        }
        
        $tokenData['gto_mail_sent_num'] = new \Zend_Db_Expr('gto_mail_sent_num + 1');
        $tokenData['gto_mail_sent_date'] = \MUtil_Date::format(new \Zend_Date(), 'yyyy-MM-dd');

        $this->db->update('gems__tokens', $tokenData, $this->db->quoteInto('gto_id_token = ?', $tokenId));
    }
}
