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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Controller for looking at mail activity
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_RespondentMailLogAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'browse'        => true,
        'containingId'  => 'autofilter_target',
        'keyboard'      => true,
        'onEmpty'       => 'getOnEmptyText',
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        'extraSort'   => array('grco_created' => SORT_DESC)
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Respondent_MailLogSnippet';


    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'Mail_Log_RespondentMailLogSearchSnippet');


    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Generic_CurrentButtonRowSnippet');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new Gems_Model_JoinModel('maillog', 'gems__log_respondent_communications');

        $model->addLeftTable('gems__respondents', array('grco_id_to' => 'grs_id_user'));
        $model->addLeftTable('gems__staff', array('grco_id_by' => 'gsf_id_user'));
        $model->addLeftTable('gems__mail_templates', array('grco_id_message' => 'gmt_id_message'));

        $model->addLeftTable('gems__tokens', array('grco_id_token' => 'gto_id_token'));
        $model->addLeftTable('gems__reception_codes', array('gto_reception_code' => 'grc_id_reception_code'));
        $model->addLeftTable('gems__tracks', array('gto_id_track' => 'gtr_id_track'));
        $model->addLeftTable('gems__surveys', array('gto_id_survey' => 'gsu_id_survey'));

        $model->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');
        $model->addColumn(
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
        $model->addColumn($this->util->getTokenData()->getStatusExpression(), 'status');

        $model->addTable('gems__respondent2org', array('grs_id_user' => 'gr2o_id_user'), 'gr2o');
        $model->setKeys(array(MUtil_Model::REQUEST_ID1  => 'gr2o_patient_nr', MUtil_Model::REQUEST_ID2 => 'gr2o_id_organization'));
        
        $model->addTable(    'gems__groups',           array('gsu_id_primary_group' => 'ggp_id_group'));        
        $model->addLeftTable('gems__rounds',           array('gto_id_round' => 'gro_id_round'));
        $model->addLeftTable('gems__staff', array('gto_by' => 'gems__staff_2.gsf_id_user'));
        $model->addColumn('CASE WHEN gems__staff_2.gsf_id_user IS NULL THEN 
                ggp_name
                ELSE COALESCE(CONCAT_WS(" ", CONCAT(COALESCE(gems__staff_2.gsf_last_name,"-"),","), gems__staff_2.gsf_first_name, gems__staff_2.gsf_surname_prefix)) END', 'ggp_name');
        
        

        $model->resetOrder();

        $model->set('grco_created',    'label', $this->_('Date sent'));
        $model->set('respondent_name', 'label', $this->_('Receiver'));
        $model->set('grco_address',    'label', $this->_('To address'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');
        $model->set('assigned_by',     'label', $this->_('Sender'));
        $model->set('grco_sender',     'label', $this->_('From address'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');
        $model->set('grco_id_token',   'label', $this->_('Token'));
        $model->set('grco_topic',      'label', $this->_('Subject'));
        $model->set('gtr_track_name',  'label', $this->_('Track'));
        $model->set('gsu_survey_name', 'label', $this->_('Survey'));
        $model->set('status',          'label', $this->_('Status'),
                'formatFunction', array($this->util->getTokenData(), 'getStatusDescription'));


        if ($detailed) {
            $model->set('gmt_subject', 'label', $this->_('Template'));
        } else {
            $model->set('grco_created', 'formatFunction', $this->util->getTranslated()->formatDate);
        }
        $model->set('ggp_name', 'label', $this->translate->getAdapter()->_('Fill out by'));

        $filter = $this->util->getRequestCache('index', $detailed)->getProgramParams();

        // Add the period filter - if any
        if ($where = Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($filter, $this->db)) {
            $model->addFilter(array($where));
        }

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        $respondent = $this->loader->getRespondent(
                $this->getRequest()->getParam(MUtil_Model::REQUEST_ID1), 
                $this->getRequest()->getParam(MUtil_Model::REQUEST_ID2)
            );
        return sprintf($this->translate->_('Mail Activity Log for %s'), $respondent->getName());
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('mail activity', 'mail activities', $count);
    }
}
