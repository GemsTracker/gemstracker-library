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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Prepares displays of respondent information
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class Gems_Snippets_RespondentDetailSnippetAbstract extends \Gems_Snippets_MenuSnippetAbstract
{
    /**
     * Optional: array of buttons
     *
     * @var array
     */
    protected $buttons;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Model_RespondentModel
     */
    protected $model;

    /**
     * Optional: href for onclick
     *
     * @var \MUtil_Html_HrefArrayAttribute
     */
    protected $onclick;

    /**
     * Optional: repaeter respondentData
     *
     * @var \MUtil_Lazy_RepeatableInterface
     */
    protected $repeater;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional: not always filled, use repeater
     *
     * @var array
     */
    protected $respondentData;

    /**
     * Optional: set display of buttons on or off
     *
     * @var boolean
     */
    protected $showButtons = true;

    /**
     * Show a warning if informed consent has not been set
     *
     * @var boolean
     */
    protected $showConsentWarning = true;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function addButtons(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        if ($this->showButtons) {
            if ($this->buttons) {
                $bridge->tfrow($this->buttons, array('class' => 'centerAlign'));
            } else {
                $menuList = $this->menu->getCurrentMenuList($this->request, $this->_('Cancel'));
                $menuList->addParameterSources($bridge);

                $bridge->tfrow($menuList, array('class' => 'centerAlign'));
            }
        }
    }

    /**
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    protected function addOnClick(\MUtil_Model_Bridge_VerticalTableBridge $bridge)
    {
        if ($this->onclick) {
            $bridge->tbody()->onclick = array('location.href=\'', $this->onclick, '\';');
        }
    }

    /**
     * Place to set the data to display
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @return void
     */
    abstract protected function addTableCells(\MUtil_Model_Bridge_VerticalTableBridge $bridge);

    /**
     * Check if we have the 'Unknown' consent, and present a warning. The project default consent is
     * normally 'Unknown' but this can be overruled in project.ini so checking for default is not right
     *
     * @static boolean $warned We need only one warning in case of multiple consents
     * @param string $consent
     */
    public function checkConsent($consent)
    {
        static $warned;

        if ($warned) {
            return $consent;
        }

        $unknown = $this->util->getConsentUnknown();

        // Value is translated by now if in bridge
        if (($consent == $unknown) || ($consent == $this->_($unknown))) {

            $warned = true;
            $msg    = $this->_('Please settle the informed consent form for this respondent.');

            if ($this->view instanceof \Zend_View) {
                $url[$this->request->getControllerKey()] = 'respondent';
                $url[$this->request->getActionKey()]     = 'edit';
                $url[\MUtil_Model::REQUEST_ID1]           = $this->request->getParam(\MUtil_Model::REQUEST_ID1);
                $url[\MUtil_Model::REQUEST_ID2]           = $this->request->getParam(\MUtil_Model::REQUEST_ID2);

                $urlString = $this->view->url($url) . '#tabContainer-frag-4';

                $this->addMessage(\MUtil_Html::create()->a($urlString, $msg));
            } else {
                $this->addMessage($msg);
            }
        }

        return $consent;
    }

    /**
     * Returns the caption for this table
     *
     * @param boolean $onlyNotCurrent Only return a string when the organization is different
     * @return string
     */
    protected function getCaption($onlyNotCurrent = false)
    {
        $orgId = $this->request->getParam(\MUtil_Model::REQUEST_ID2);
        if ($orgId == $this->loader->getCurrentUser()->getCurrentOrganizationId()) {
            if ($onlyNotCurrent) {
                return;
            } else {
                return $this->_('Respondent information');
            }
        } else {
            return sprintf($this->_('%s respondent information'), $this->loader->getOrganization($orgId)->getName());
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $bridge = $this->model->getBridgeFor('itemTable', array('class' => 'displayer table table-condensed'));
        $bridge->setRepeater($this->repeater);
        $bridge->setColumnCount(2); // May be overruled

        $this->addTableCells($bridge);
        $this->addButtons($bridge);
        $this->addOnClick($bridge);

        $container = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $container[] = $bridge->getTable();
        return $container;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->model) {
            $this->model->setIfExists('grs_email', 'itemDisplay', 'MUtil_Html_AElement::ifmail');
            $this->model->setIfExists('gr2o_comments', 'rowspan', 2);

            if ($this->showConsentWarning && $this->model->has('gr2o_consent')) {
                $this->model->set('gr2o_consent', 'formatFunction', array($this, 'checkConsent'));
            }

            if (! $this->repeater) {
                if (! $this->respondentData) {
                    $this->repeater = $this->model->loadRepeatable();
                } else {
                    // In case a single array of values was passed: make nested
                    if (is_array(reset($this->respondentData))) {
                        $data = $this->respondentData;
                    } else {
                        $data = array($this->respondentData);
                    }

                    $this->repeater = \MUtil_Lazy::repeat($data);
                }
            }

            return true;
        }

        return false;
    }
}
