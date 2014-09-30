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
 *    * Neither the name of the Erasmus MC nor the
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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class DeleteSingleSurveyNotUsedTokenSnippet extends Gems_Tracker_Snippets_ShowTokenSnippetAbstract
{
    const CONFIRMED = 'confirmed';

    /**
     *
     * @var mixed The url to route to
     */
    protected $_route;

    /**
     * Variable to either keep or throw away the request data
     * not specified in the route.
     *
     * @var boolean True then the route is reset
     */
    public $resetRoute = true;

    /**
     * Show the token in an mini form for cut & paste.
     *
     * But only when the token is not answered.
     *
     * @var boolean
     */
    protected $useFakeForm = false;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(MUtil_Model_Bridge_VerticalTableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $question = $this->_('Are you sure?');
        $bridge->caption($question);

        // Token ID
        $bridge->addItem('gto_id_token');
        $bridge->tr()->td(array('colspan' => 2));

        // Respondent
        $bridge->addItem('gr2o_patient_nr');
        $bridge->addItem('respondent_name');
        $bridge->tdrow();

        // Survey
        $bridge->addItem('gsu_survey_name');
        $bridge->addItem('gr2t_track_info');
        $bridge->addItem('assigned_by');
        $bridge->addItem('ggp_name');
        $bridge->tdrow();

        // Token
        $bridge->addItem('gto_valid_from');
        $bridge->addItem('gto_valid_until');
        $bridge->addItem('gto_comment');
        $bridge->tdrow();

        // E-MAIL
        $bridge->addItem('gto_mail_sent_date');

        // Yes / no
        $footer = $bridge->tfrow($question, ' ', array('class' => 'centerAlign'));
            $footer->actionLink(array(self::CONFIRMED => 1), $this->_('Yes'));
            $footer->actionLink(array('action' => 'show'), $this->_('No'));

        // Other buttons
        $bridge->tfrow($this->getMenuList(), array('class' => 'centerAlign'));
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::getModel();

        $model->setBridgeFor('itemTable', 'VerticalTableBridge');

        return $model;
    }

    /**
     *
     * @return Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $controller = $this->request->getControllerName();

        $links->addByController($controller, 'show', $this->_('Show token'))
                ->addByController($controller, 'edit', $this->_('Edit token'))
                ->addByController($controller, 'index', $this->_('Show surveys'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    }

    /**
     * When hasHtmlOutput() is false a snippet code user should check
     * for a redirectRoute. Otherwise the redirect calling render() will
     * execute the redirect.
     *
     * This function should never return a value when the snippet does
     * not redirect.
     *
     * Also when hasHtmlOutput() is true this function should not be
     * called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return $this->_route;
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        return $this->_('Delete unused survey');
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (parent::hasHtmlOutput()) {
            if ($this->token && $this->token->exists) {

                // Delete the track
                if ($this->request->getParam(self::CONFIRMED)) {
                    // We need both keys in the filter, otherwise
                    // the track won't be deleted.
                    $filter['gto_id_token'] = $this->tokenId;
                    $filter['gr2t_id_respondent_track'] = $this->token->getRespondentTrackId();

                    $model = $this->token->getModel();
                    $changed = $model->delete($filter);

                    // Inform the user
                    $this->addMessage(sprintf($this->_('Deleted token %s for survey %s.'), strtoupper($this->tokenId), $this->token->getSurveyName()));

                    // Build a full route, throw all previous data away.
                    $this->_route = array(
                        $this->request->getControllerKey() => $this->request->getControllerName(),
                        $this->request->getActionKey() => 'view',
                        MUtil_Model::REQUEST_ID => $this->token->getPatientNumber(),
                        Gems_Model::TRACK_ID => $this->token->getTrackId());

                    return false;
                }

                return true;
            }
        }

        return false;
    }
}
