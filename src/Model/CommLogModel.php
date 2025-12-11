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

use Gems\Menu\RouteHelper;
use Gems\Model;
use Gems\Repository\CommJobRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\User\Group;
use Zalt\Html\AElement;
use Zalt\Html\Html;

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 12:55:25
 */
class CommLogModel extends HiddenOrganizationModel
{
    /**
     * @var CommJobRepository
     */
    protected $commJobRepository;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * @var RouteHelper
     */
    protected $routeHelper;

    /**
     * @var TokenRepository
     */
    protected $tokenRepository;

    /**
     * @var Tracker
     */
    protected $tracker;

    /**
	 * Create the mail template model
	 */
	public function __construct()
	{
		parent::__construct('maillog', 'gems__log_respondent_communications');

        $this->addTable('gems__respondents', ['grco_id_to' => 'grs_id_user']);
        $this->addTable(
                'gems__respondent2org',
                ['grco_id_to' => 'gr2o_id_user', 'grco_organization' => 'gr2o_id_organization']
                );

        $this->addLeftTable('gems__staff', ['grco_id_by' => 'gsf_id_user']);

        $this->addLeftTable('gems__tokens', ['grco_id_token' => 'gto_id_token']);
        $this->addLeftTable('gems__reception_codes', ['gto_reception_code' => 'grc_id_reception_code']);
        $this->addLeftTable('gems__tracks', ['gto_id_track' => 'gtr_id_track']);
        $this->addLeftTable('gems__surveys', ['gto_id_survey' => 'gsu_id_survey']);

        $this->addLeftTable('gems__groups', ['gsu_id_primary_group' => 'ggp_id_group']);
        $this->addLeftTable(['gems__staff', 'staff_by'],  ['gto_by' => 'staff_by.gsf_id_user']);
        $this->addLeftTable('gems__track_fields',         ['gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"']
        );       // Add relation fields
        $this->addLeftTable('gems__respondent_relations', ['gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent']
        ); // Add relation

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

        $this->addColumn(new \Zend_Db_Expr($this->tokenRepository->getStatusExpression()->getExpression()), 'status');
    }

    /**
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     */
    public function applySetting($detailed = true)
    {
        if ($detailed) {
            $this->addLeftTable('gems__comm_templates', ['grco_id_message' => 'gct_id_template']);
            $this->addLeftTable('gems__comm_jobs', ['grco_id_job' => 'gcj_id_job']);
        }

        $this->resetOrder();
        
        $this->setKeys([Model::LOG_ITEM_ID => 'grco_id_action']);

        $this->set('grco_created',    [
            'label' => $this->_('Date sent'),
            'dateFormat' => Tracker::DB_DATETIME_FORMAT
        ]);
        $this->set('gr2o_patient_nr', [
            'label' => $this->_('Respondent nr'),
        ]);
        $this->set('respondent_name', [
            'label' => $this->_('Receiver'),
        ]);
        $this->set('grco_address',    [
            'label' => $this->_('To address'),
            'itemDisplay' => [AElement::class, 'ifmail'],
        ]);
        $this->set('assigned_by',     [
            'label' => $this->_('Sender'),
        ]);
        $this->set('grco_sender',     [
            'label' => $this->_('From address'),
            'itemDisplay' => [AElement::class, 'ifmail'],
        ]);
        $this->set('grco_id_token',   [
            'label' => $this->_('Token'),
            'itemDisplay' => [$this, 'displayToken'],
        ]);
        $this->set('grco_topic',      [
            'label' => $this->_('Subject'),
        ]);
        $this->set('gtr_track_name',  [
            'label' => $this->_('Track'),
        ]);
        $this->set('gsu_survey_name', [
            'label' => $this->_('Survey'),
        ]);
        $this->set('filler',          [
            'label' => $this->_('Fill out by'),
        ]);
        $this->set('status',          [
            'label' => $this->_('Status'),
            'formatFunction' => [$this->tokenRepository, 'getStatusDescription']
        ]);

        if ($detailed) {
            $this->set('gct_name', 'label', $this->_('Template'));
            //if ($currentUser->hasPrivilege('pr.comm.job')) {
            $this->set('grco_id_job', [
                'label' => $this->_('Job'),
                'formatFunction' => [$this, 'formatJob', true],
                'multiOptions' => $this->commJobRepository->getJobsOverview(),
            ]);
            //}
        }

        $this->applyMask();
    }

    public function formatJob($jobDescr, $jobId = null)
    {
        if ($jobId) {
            $url = $this->routeHelper->getRouteUrl('setup.communication.job.show', [
                Model::REQUEST_ID => $jobId,
            ]);

            return Html::create('a', $url, $jobDescr);
        }

        return Html::create('em', $this->_('manual'));
    }

    public function displayToken($tokenId)
    {
        if ($tokenId) {
            $token = $this->tracker->getToken($tokenId);
            $url = $this->routeHelper->getRouteUrl('respondent.tracks.token.show', [
                Model::REQUEST_ID1 => $token->getPatientNumber(),
                Model::REQUEST_ID2 => $token->getOrganizationId(),
                \Gems\Model::RESPONDENT_TRACK => $token->getRespondentTrackId(),
                Model::REQUEST_ID => $tokenId,
            ]);

            return Html::create('a', $url, $tokenId);
        }
        return $tokenId;
    }
}
