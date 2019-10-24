<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\User\Group;

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 12:55:25
 */
class CommLogModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
	 * Create the mail template model
	 */
	public function __construct()
	{
		parent::__construct('maillog', 'gems__log_respondent_communications');

        $this->addTable('gems__respondents', array('grco_id_to' => 'grs_id_user'));
        $this->addTable(
                'gems__respondent2org',
                array('grco_id_to' => 'gr2o_id_user', 'grco_organization' => 'gr2o_id_organization')
                );

        $this->addLeftTable('gems__staff', array('grco_id_by' => 'gsf_id_user'));

        $this->addLeftTable('gems__tokens', array('grco_id_token' => 'gto_id_token'));
        $this->addLeftTable('gems__reception_codes', array('gto_reception_code' => 'grc_id_reception_code'));
        $this->addLeftTable('gems__tracks', array('gto_id_track' => 'gtr_id_track'));
        $this->addLeftTable('gems__surveys', array('gto_id_survey' => 'gsu_id_survey'));

        $this->addLeftTable('gems__groups', array('gsu_id_primary_group' => 'ggp_id_group'));
        $this->addLeftTable(array('gems__staff', 'staff_by'),  array('gto_by' => 'staff_by.gsf_id_user'));
        $this->addLeftTable('gems__track_fields',         array('gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"'));       // Add relation fields
        $this->addLeftTable('gems__respondent_relations', array('gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent')); // Add relation

        $this->addColumn(
            "TRIM(CONCAT(
                COALESCE(CONCAT(grs_last_name, ', '), '-, '),
                COALESCE(CONCAT(grs_first_name, ' '), ''),
                COALESCE(grs_surname_prefix, '')
                ))",
            'respondent_name');
        $this->addColumn(
            "CASE WHEN gems__staff.gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    )
                END",
            'assigned_by');

        $this->addColumn('CASE WHEN staff_by.gsf_id_user IS NULL THEN
                    COALESCE(gems__track_fields.gtf_field_name, ggp_name)
                    ELSE CONCAT_WS(
                        " ",
                        staff_by.gsf_first_name,
                        staff_by.gsf_surname_prefix,
                        COALESCE(staff_by.gsf_last_name, "-")
                        )
                    END',
                'filler');
	}

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->addColumn($this->util->getTokenData()->getStatusExpression(), 'status');

        if (! $this->request instanceof \Zend_Controller_Request_Abstract) {
            $this->request = \Zend_Controller_Front::getInstance()->getRequest();
        }
    }

    /**
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     */
    public function applySetting($detailed = true)
    {
        if ($detailed) {
            $this->addLeftTable('gems__comm_templates', array('grco_id_message' => 'gct_id_template'));
            $this->addLeftTable('gems__comm_jobs', array('grco_id_job' => 'gcj_id_job'));
        }

        $this->resetOrder();

        $this->set('grco_created',    'label', $this->_('Date sent'));
        if ($detailed) {
            $this->set('grco_created', 'formatFunction', $this->util->getTranslated()->formatDate);
        }
        $this->set('gr2o_patient_nr', 'label', $this->_('Respondent nr'));
        $this->set('respondent_name', 'label', $this->_('Receiver'));
        $this->set('grco_address',    'label', $this->_('To address'), 'itemDisplay', array('MUtil_Html_AElement', 'ifmail'));
        $this->set('assigned_by',     'label', $this->_('Sender'));
        $this->set('grco_sender',     'label', $this->_('From address'), 'itemDisplay', array('MUtil_Html_AElement', 'ifmail'));
        $this->set('grco_id_token',   'label', $this->_('Token'), 'itemDisplay', array($this, 'displayToken'));
        $this->set('grco_topic',      'label', $this->_('Subject'));
        $this->set('gtr_track_name',  'label', $this->_('Track'));
        $this->set('gsu_survey_name', 'label', $this->_('Survey'));
        $this->set('filler',          'label', $this->_('Fill out by'));
        $this->set('status',          'label', $this->_('Status'),
                'formatFunction', array($this->util->getTokenData(), 'getStatusDescription'));

        if ($detailed) {
            $this->set('gct_name', 'label', $this->_('Template'));

            if ($this->currentUser->hasPrivilege('pr.comm.job')) {
                $this->set('grco_id_job', 'label', $this->_('Job'), 'formatFunction', [$this, 'formatJob']);
            }
        }

        $this->refreshGroupSettings();
    }

    public function formatJob($jobId)
    {
        if ($jobId) {
            $url = new \MUtil_Html_HrefArrayAttribute(array(
                $this->request->getControllerKey() => 'comm-job',
                $this->request->getActionKey()     => 'show',
                \MUtil_Model::REQUEST_ID           => $jobId,
            ));


            return \MUtil_Html::create('a', $url, $jobId);
        }
    }

    public function displayToken($token)
    {
        if ($token) {
            $url = new \MUtil_Html_HrefArrayAttribute(array(
                $this->request->getControllerKey() => 'track',
                $this->request->getActionKey()     => 'show',
                \MUtil_Model::REQUEST_ID           => $token,
                ));


            return \MUtil_Html::create('a', $url, $token);
        }
    }

    /**
     * Function to re-apply all the masks and settings for the current group
     *
     * @return void
     */
    public function refreshGroupSettings()
    {
        $group = $this->currentUser->getGroup();
        if ($group instanceof Group) {
            $group->applyGroupToModel($this, false);
        }
    }
}
