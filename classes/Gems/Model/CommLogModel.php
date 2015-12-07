<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CommLogModel.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Model;

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
                    ggp_name
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
}
