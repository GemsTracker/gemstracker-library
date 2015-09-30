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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ModelConfirmDataChangeSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace MUtil\Snippets;

/**
 * A snippet asking for confirmation before performing a save of predertemined data
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.7.2 30-sep-2015 18:49:24
 */
abstract class ModelConfirmDataChangeSnippetAbstract extends \MUtil_Snippets_ModelVerticalTableSnippetAbstract
{
    /**
     * The action to go to when the user clicks 'No'.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $abortAction = 'show';

    /**
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @var mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    protected $afterSaveRouteUrl;

    /**
     * Optional class for use on buttons, overruled by $buttonNoClass and $buttonYesClass
     *
     * @var string
     */
    protected $buttonClass;

    /**
     * Optional class for use on No button
     *
     * @var string
     */
    protected $buttonNoClass;

    /**
     * Optional class for use on Yes button
     *
     * @var string
     */
    protected $buttonYesClass;

    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags;

    /**
     * The action to go to when the user clicks 'Yes' and the data is changed.
     *
     * If you want to change to another controller you'll have to code it.
     *
     * @var string
     */
    protected $confirmAction = 'show';

    /**
     * The question to ask the user.
     *
     * @var sting Required
     */
    protected $confirmQuestion;

    /**
     * The request parameter used to store the confirmation
     *
     * @var string Required
     */
    protected $confirmParameter = 'confirmed';

    /**
     * Variable to either keep or throw away the request data
     * not specified in the route.
     *
     * @var boolean True then the route is reset
     */
    public $resetRoute = false;

    /**
     * Required: teh data to save to the model when saving
     *
     * @var array
     */
    protected $saveData;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if (! $this->saveData) {
            throw new \Zend_Exception("No data to save specified while using " . __CLASS__);
        }

        if ($this->buttonClass) {
            if (! $this->buttonNoClass) {
                $this->buttonNoClass = $this->buttonClass;
            }
            if (! $this->buttonYesClass) {
                $this->buttonYesClass = $this->buttonClass;
            }
        }
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    // protected function createModel()

    /**
     * The question.
     *
     * @return string
     */
    protected function getQuestion()
    {
        if ($this->confirmQuestion) {
            return $this->confirmQuestion;
        } else {
            return $this->_('Are you sure?');
        }
    }

    /**
     * When hasHtmlOutput() is false a snippet user should check
     * for a redirectRoute.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return $this->afterSaveRouteUrl;
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
        if ($this->request->getParam($this->confirmParameter)) {
            $this->performAction();

            $redirectRoute = $this->getRedirectRoute();
            return empty($redirectRoute);

        } else {
            return parent::hasHtmlOutput();
        }
    }

    /**
     * Overrule this function if you want to perform a different
     * action than deleting when the user choose 'yes'.
     */
    protected function performAction()
    {
        $model = $this->getModel();

        $model->save($this->saveData + $model->getFilter());

        if ($this->cacheTags && ($this->cache instanceof \Zend_Cache_Core)) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, (array) $this->cacheTags);
        }

        $this->setAfterDeleteRoute();
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil_Snippets_ModelYesNoDeleteSnippetAbstract
     */
    protected function setAfterDeleteRoute()
    {
        // Default is just go to the index
        if ($this->confirmAction && ($this->request->getActionName() !== $this->confirmAction)) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => $this->request->getControllerName(),
                $this->request->getActionKey() => $this->confirmAction,
                $this->confirmParameter => null, // make empty
                );
        }

        }
    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $footer = $bridge->tfrow();

        $footer[] = $this->getQuestion();
        $footer[] = ' ';
        $footer->a(
                array($this->confirmParameter => 1),
                $this->_('Yes'),
                array('class' => $this->buttonYesClass)
                );
        $footer[] = ' ';
        $footer->a(
                array($this->request->getActionKey() => $this->abortAction),
                $this->_('No'),
                array('class' => $this->buttonNoClass)
                );
    }
}
