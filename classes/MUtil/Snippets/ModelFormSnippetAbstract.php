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
 * @package    MUtil
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Abstract class for creating & processing a form based on a model. To use this 
 * class either subclass or use the existing default ModelFormSnippet.
 * 
 * The processForm() method executes e sequence of methods that
 * depending on the input display the form or save the form and 
 * redirects the output to another controller/action.
 *
 * @see ModelFormSnippet
 * 
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class MUtil_Snippets_ModelFormSnippetAbstract extends MUtil_Snippets_ModelSnippetAbstract
{
    /**
     *
     * @var Zend_Form
     */
    protected $_form;

    /**
     *
     * @var Zend_Form_Element_Submit
     */
    protected $_saveButton;

    /**
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @var mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    protected $afterSaveRouteUrl;

    /**
     *
     * @var string class attribute for buttons
     */
    protected $buttonClass = 'button';

    /**
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected $createData = false;

    /**
     * As it is better for translation utilities to set the labels etc. translated,
     * the MUtil default is to disable translation.
     *
     * However, this also disables the translation of validation messages, which we
     * cannot set translated. The MUtil form is extended so it can make this switch.
     *
     * @var boolean True
     */
    protected $disableValidatorTranslation = false;

    /**
     *
     * @var array
     */
    protected $formData = array();

    /**
     *
     * @var string class attribute for labels
     */
    protected $labelClass = 'label';

    /**
     * Required
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'index';

    /**
     * The form Id used for the save button
     *
     * If empty save button is added
     *
     * @var string
     */
    protected $saveButtonId = 'save_button';

    /**
     * The save button label (default is translated 'Save'
     *
     * @var string
     */
    protected $saveLabel = null;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        foreach($model->getItemsOrdered() as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->add($name);
            } else {
                $bridge->addHidden($name);
            }
        }
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // Communicate to user
        if ($changed) {
            $this->addMessage(sprintf($this->_('%2$u %1$s saved'), $this->getTopic($changed), $changed));
        } else {
            $this->addMessage($this->_('No changes to save!'));
        }
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     *
     * @return Zend_Form
     */
    protected function beforeDisplay()
    { }

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addSaveButton()
    {
        if ($this->_saveButton) {
            $this->saveButtonId = $this->_saveButton->getName();

            if (! $this->_form->getElement($this->saveButtonId)) {
                $this->_form->addElement($this->_saveButton);
            }
        } elseif ($this->saveButtonId) {
            //If not already there, add a save button
            $this->_saveButton = $this->_form->getElement($this->saveButtonId);

            if (! $this->_saveButton) {
                if (null === $this->saveLabel) {
                    $this->saveLabel = $this->_('Save');
                }

                $this->_saveButton = new Zend_Form_Element_Submit($this->saveButtonId, $this->saveLabel);
                if ($this->buttonClass) {
                    $this->_saveButton->setAttrib('class', $this->buttonClass);
                }
                $this->_form->addElement($this->_saveButton);
            }
        }
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return (boolean) $this->request;
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return Zend_Form
     */
    protected function createForm($options = null)
    {
        // $form = new Zend_Form($options);
        $form = new MUtil_Form($options);

        return $form;
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
        // Hook for subclasses
        $this->beforeDisplay();

        // Again, just to be sure all changes are set on the form
        $this->populateForm();

        return $this->_form;
    }

    /**
     * Creates from the model a Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @return Zend_Form
     */
    protected function getModelForm()
    {
        $model    = $this->getModel();
        $baseform = $this->createForm();
        $bridge   = new MUtil_Model_FormBridge($model, $baseform);

        $this->addFormElements($bridge, $model);

        return $bridge->getForm();
    }

    /**
     * When hasHtmlOutput() is false a snippet user should check
     * for a redirectRoute.
     *
     * When hasHtmlOutput() is true this functions should not be called.
     *
     * @see Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return $this->afterSaveRouteUrl;
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('item', 'items', $count);
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
            return $this->processForm();
        }
    }

    /**
     * Makes sure there is a form.
     */
    protected function loadForm()
    {
        if (! $this->_form) {
            $this->_form = $this->getModelForm();
        }
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if ($this->request->isPost()) {
            $this->formData = $this->request->getPost() + $this->formData;
        } else {
            $model = $this->getModel();

            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                if ($this->createData) {
                    $this->formData = $model->loadNew();
                } else {
                    $this->formData = $model->loadFirst();

                    if (! $this->formData) {
                        throw new Zend_Exception($this->_('Unknown edit data requested'));
                    }
                }
            }
        }
    }


    /**
     * Hook that allows actions when the form is submitted, but it was not the submit button that was checked
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onFakeSubmit()
    { }

    /**
     * Hook that allows actions when the input is invalid
     *
     * When not rerouted, the form will be populated afterwards
     */
    protected function onInValid()
    {
        $this->addMessage(sprintf($this->_('Input error! Changes to %s not saved!'), $this->getTopic()));
    }

    /**
     * Hook for setting the data on the form.
     */
    protected function populateForm()
    {
        $this->_form->populate($this->formData);
    }

    /**
     * Step by step form processing
     *
     * Returns false when $this->afterSaveRouteUrl is set during the
     * processing, which happens by default when the data is saved.
     *
     * @return boolean True when the form should be displayed
     */
    protected function processForm()
    {
        // Make sure there is $this->formData
        $this->loadFormData();

        // Make sure there is a $this->_form
        $this->loadForm();

        // Create $this->_saveButton
        $this->addSaveButton();

        if ($this->request->isPost()) {
            //First populate the form, otherwise the saveButton will never be 'checked'!
            $this->populateForm();

            // If there is a save button it should be checked, otherwise just validate
            if ((! $this->_saveButton) || $this->_saveButton->isChecked()) {
                if ($this->validateForm()) {
                    /*
                     * Now that we validated, the form is be populated. But I think the step
                     * below is not needed as the values in the form come from the data array
                     * but performing a getValues() cleans the data array so data in post but
                     * not in the form is removed from the data variable.
                     */
                    $this->formData = $this->_form->getValues();

                    // Save
                    $this->saveData();

                    // Reroute (always, override function otherwise)
                    $this->setAfterSaveRoute();

                } else {
                    $this->onInValid();
                }
            } else {
                //The default save button was NOT used, so we have a fakesubmit button
                $this->onFakeSubmit();
            }
        }

        return ! $this->getRedirectRoute();
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        // Perform the save
        $model          = $this->getModel();
        $this->formData = $model->save($this->formData);
        $changed        = $model->getChanged();

        // Perform the save
        $this->afterSave($changed);
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array($this->request->getActionKey() => $this->routeAction);
        }

        return $this;
    }

    /**
     * Performs the validation.
     *
     * @return boolean True if validation was OK and data should be saved.
     */
    protected function validateForm()
    {
        // Note we use an MUtil_Form
        return $this->_form->isValid($this->formData, $this->disableValidatorTranslation);
    }
}
