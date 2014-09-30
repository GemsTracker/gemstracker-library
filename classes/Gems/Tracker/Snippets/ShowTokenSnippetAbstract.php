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
 * @version    $Id$
 */

/**
 * Generic extension for displaying tokens
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Tracker_Snippets_ShowTokenSnippetAbstract extends MUtil_Snippets_ModelVerticalTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer';

    /**
     * Required
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var Gems_Tracker_Token
     */
    protected $token;

    /**
     * Optional: id of the selected token to show
     *
     * Can be derived from $request or $token
     *
     * @var string
     */
    protected $tokenId;

    /**
     * Show the token in an mini form for cut & paste.
     *
     * But only when the token is not answered.
     *
     * @var boolean
     */
    protected $useFakeForm = true;

    /**
     *
     * @var Zend_View
     */
    protected $view;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader && $this->menu && $this->request;
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->token->getModel();

        if ($this->useFakeForm && $this->token->hasSuccesCode() && (! $this->token->isCompleted())) {
            $model->set('gto_id_token', 'formatFunction', array(__CLASS__, 'makeFakeForm'));
        } else {
            $model->set('gto_id_token', 'formatFunction', 'strtoupper');
        }
        $model->setBridgeFor('itemTable', 'ThreeColumnTableBridge');

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
        if ($this->tokenId) {
            if ($this->token->exists) {
                $htmlDiv   = MUtil_Html::div();

                $htmlDiv->h3($this->getTitle());

                $table = parent::getHtmlOutput($view);
                $this->applyHtmlAttributes($table);
                $htmlDiv[] = $table;

                return $htmlDiv;

            } else {
                $this->addMessage(sprintf($this->_('Token %s not found.'), $this->view->escape($this->tokenId)));
            }

        } else {
            $this->addMessage($this->_('No token specified.'));
        }
    }

    /**
     *
     * @return string The header title to display
     */
    abstract protected function getTitle();

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
            if ($this->token) {
                $this->tokenId = $this->token->getTokenId();
            } elseif ($this->request) {
                $this->tokenId = $this->request->getParam(MUtil_Model::REQUEST_ID);
            }
        }

        if ($this->tokenId && (! $this->token)) {
            $this->token = $this->loader->getTracker()->getToken($this->tokenId);
        }

        // Output always true, returns an error message as html when anything is wrong
        return true;
    }

    /**
     * Creates a fake form so that it is (slightly) easier to
     * copy and paste a token.
     *
     * @param string $value Gems token value
     * @return Gems_Form
     */
    public static function makeFakeForm($value)
    {
        $form = new Gems_Form();
        $form->removeDecorator('HtmlTag');

        $element = new Zend_Form_Element_Text('gto_id_token');
        $element->class = 'token_copy';
        $element->setDecorators(array('ViewHelper', array('HtmlTag', 'Div')));

        $form->addElement($element);
        $form->isValid(array('gto_id_token' => MUtil_Lazy::call('strtoupper', $value)));

        return $form;
    }
}
