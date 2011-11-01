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
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AnswerModelSnippet.php 28 2011-09-16 06:24:15Z mennodekker $
 */

/**
 * Displays answers to a survey.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Snippets_AnswerModelSnippetGeneric extends Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('gto_round_order' => SORT_ASC);

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser';

    /**
     *
     * @var string Format used for displaying dates.
     */
    protected $dateFormat = Zend_Date::DATE_MEDIUM;

    /**
     * Required
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     * Switch to put the display of the cancel and pritn buttons.
     *
     * @var boolean
     */
    protected $showButtons = true;

    /**
     * Switch to put the display of the current token as select to true or false.
     *
     * @var boolean
     */
    protected $showSelected = true;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     * Required: id of the selected token to show
     *
     * @var string
     */
    protected $tokenId;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $br = MUtil_Html::create('br');
        if ($this->showSelected) {
            $selectedClass = MUtil_Lazy::iff(MUtil_Lazy::comp($bridge->gto_id_token, '==', $this->tokenId), 'selectedColumn', null);
        } else {
            $selectedClass = null;
        }

        $bridge->th($this->_('Status'));
        $td = $bridge->tdh(MUtil_Lazy::first($bridge->grc_description, $this->_('OK')));
        $td->appendAttrib('class', $selectedClass);

        $bridge->th($this->_('Question'));
        $td = $bridge->tdh(
                $bridge->gto_round_description,
                MUtil_Lazy::iif($bridge->gto_round_description, $br),
                MUtil_Lazy::iif($bridge->gto_completion_time, $bridge->gto_completion_time, $bridge->gto_valid_from)
                        );
        $td->appendAttrib('class', $selectedClass);
        $td->appendAttrib('class', $bridge->row_class);

        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->thd($label, array('class' => $model->get($name, 'thClass')));
                $td = $bridge->td($bridge->$name);

                $td->appendAttrib('class', 'answer');
                $td->appendAttrib('class', $selectedClass);
                $td->appendAttrib('class', $bridge->row_class);
            }
        }

        $bridge->th($this->_('Token'));

        $tokenUpper = $bridge->gto_id_token->strtoupper();
        if ($menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $source = new Gems_Menu_ParameterSource();
            $source->setTokenId($bridge->gto_id_token);
            $source->offsetSet('can_be_taken', $bridge->can_be_taken);

            $link = $menuItem->toActionLink($source);
            $link->title = array($this->_('Token'), $tokenUpper);

            $td = $bridge->tdh($bridge->can_be_taken->if($link, $tokenUpper));
        } else {
            $td = $bridge->tdh($tokenUpper);
        }
        $td->appendAttrib('class', $selectedClass);
        $td->appendAttrib('class', $bridge->row_class);
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->token->getSurveyAnswerModel($this->locale->getLanguage());

        $model->set('gto_valid_from', 'dateFormat', $this->dateFormat);
        $model->set('gto_completion_time', 'dateFormat', $this->dateFormat);

        return $model;
    }


    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $htmlDiv   = MUtil_Html::create()->div();

        if ($this->tokenId) {
            if ($this->token->exists) {
                $htmlDiv->h3(sprintf($this->_('%s answers for patient number %s'), $this->token->getSurveyName(), $this->token->getPatientNumber()));

                $htmlDiv->pInfo(sprintf(
                        $this->_('Answers for token %s, patient number %s: %s.'),
                        strtoupper($this->tokenId),
                        $this->token->getPatientNumber(),
                        $this->token->getRespondentName()))
                        ->appendAttrib('class', 'noprint');

                $table = parent::getHtmlOutput($view);
                $table->setPivot(true, 2, 1);

                $this->applyHtmlAttributes($table);
                $htmlDiv[] = $table;

            } else {
                $htmlDiv->ul(sprintf($this->_('Token %s not found.'), $this->tokenId), array('class' => 'errors'));
            }

        } else {
            $htmlDiv->ul($this->_('No token specified.'), array('class' => 'errors'));
        }

        if ($this->showButtons) {
            $buttonDiv = $htmlDiv->buttonDiv();
            $buttonDiv->actionLink(array(), $this->_('Close'), array('onclick' => 'window.close();'));
            $buttonDiv->actionLink(array(), $this->_('Print'), array('onclick' => 'window.print();'));
        }
        return $htmlDiv;
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
        if (! $this->tokenId) {
            if (isset($this->token)) {
                $this->tokenId = $this->token->getTokenId();
            }
        } elseif (! $this->token) {
            $this->token = $this->loader->getTracker()->getToken($this->tokenId);
        }

        // Output always true, returns an error message as html when anything is wrong
        return true;
    }
}
