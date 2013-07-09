<?php

/**
 * Copyright (c) 201e, Erasmus MC
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
 * @subpackage WizardFormSnippetAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $id: WizardFormSnippetAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Snippets_WizardFormSnippetAbstract extends MUtil_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var Zend_Form_Element_Submit
     */
    protected $_cancelButton;

    /**
     *
     * @var Zend_Form_Element_Submit
     */
    protected $_finishButton;

    /**
     *
     * @var array of Zend_Form's, one for each step (that is initialized)
     */
    protected $_forms = array();

    /**
     *
     * @var Zend_Form_Element_Submit
     */
    protected $_nextButton;

    /**
     *
     * @var Zend_Form_Element_Submit
     */
    protected $_previousButton;

    /**
     * The form Id used for the cancel button
     *
     * If empty cancel button is not added
     *
     * @var string
     */
    protected $cancelButtonId = 'cancel_button';

    /**
     * The cancel button label (default is translated 'Cancel')
     *
     * @var string
     */
    protected $cancelLabel = null;

    /**
     * The current step, starting at 1.
     *
     * @var int
     */
    protected $currentStep = 1;

    /**
     * The form Id used for the finish button
     *
     * If empty button is not added
     *
     * @var string
     */
    protected $finishButtonId = 'finish_button';

    /**
     * The finish button label (default is translated 'Finish')
     *
     * @var string
     */
    protected $finishLabel = null;

    /**
     * The form Id used for the next button
     *
     * If empty button is not added
     *
     * @var string
     */
    protected $nextButtonId = 'next_button';

    /**
     * The next button label (default is translated 'Next')
     *
     * @var string
     */
    protected $nextLabel = null;

    /**
     * The form Id used for the previous button
     *
     * If empty button is not added
     *
     * @var string
     */
    protected $previousButtonId = 'previous_button';

    /**
     * The previous button label (default is translated 'Previous')
     *
     * @var string
     */
    protected $previousLabel = null;

    /**
     * Name of the hidden field storing the current step
     *
     * @var string
     */
    protected $stepFieldName = 'current_step';

    /**
     * Default button creation function.
     *
     * @param Zend_Form_Element $button or null
     * @param string $buttonId
     * @param string $label
     * @param string $defaultLabel
     * @param string $class
     */
    protected function _addButton(&$button, &$buttonId, &$label, $defaultLabel, $class = 'Zend_Form_Element_Submit')
    {
        if ($button && ($button instanceof Zend_Form_Element)) {
            $buttonId = $button->getName();

        } elseif ($this->saveButtonId) {
            //If not already there, add a save button
            $button = $this->_form->getElement($buttonId);

            if (! $button) {
                if (null === $label) {
                    $label = $defaultLabel;
                }

                $button = new $class($buttonId, $label);
                if ($this->buttonClass) {
                    $button->setAttrib('class', $this->buttonClass);
                }

                $button->setDecorators(array('Tooltip', 'ViewHelper'));
            }
        }
        if (! $this->_form->getElement($buttonId)) {
            $group = $this->_form->getDisplayGroup('buttons');
            if (! $group) {
                $this->_form->addDisplayGroup(array($button), 'buttons');

                $group = $this->_form->getDisplayGroup('buttons');
                $group->setDecorators(array('FormElements'));

            } else {
                $group->addElement($button);
            }
        }
    }

    /**
     * Add the cancel button
     */
    protected function addButtons()
    {
        $this->addPreviousButton();
        $this->addNextButton();

        $element = new MUtil_Form_Element_Exhibitor('button_spacer');
        $element->setValue('&nbsp;');
        $element->setDecorators(array('ViewHelper'));

        $group = $this->_form->getDisplayGroup('buttons');
        $group->addElement($element);

        $this->addCancelButton();
        $this->addFinishButton();
    }

    /**
     * Add the cancel button
     */
    protected function addCancelButton()
    {
        $class = 'MUtil_Form_Element_FakeSubmit';
        $this->_addButton($this->_cancelButton, $this->cancelButtonId, $this->cancelLabel, $this->_('Cancel'), $class);
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    protected function addFormElementsFor(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, $step)
    {
        //Get all elements in the model if not already done
        $this->initItems();

        // Store the current step
        $bridge->addHidden($this->stepFieldName);

        $this->addStepElementsFor($bridge, $model, $step);

        //And any remaining item
        $this->addItemsHidden($bridge, $this->_items);
    }

    /**
     * Add the finish button
     */
    protected function addFinishButton()
    {
        $last  = $this->currentStep == $this->getStepCount();
        $class = $last ? 'Zend_Form_Element_Submit' : 'MUtil_Form_Element_FakeSubmit';

        $this->_addButton($this->_finishButton, $this->finishButtonId, $this->finishLabel, $this->_('Finish'), $class);
        if (! $last) {
            $this->_finishButton->setAttrib('disabled', 'disabled');
        } else {
            $this->_finishButton->setAttrib('disabled', null);
        }
    }

    /**
     * Add the next button
     */
    protected function addNextButton()
    {
        $last  = $this->currentStep == $this->getStepCount();
        $class = !$last ? 'Zend_Form_Element_Submit' : 'MUtil_Form_Element_FakeSubmit';

        $this->_addButton($this->_nextButton, $this->nextButtonId, $this->nextLabel, $this->_("Next >"), $class);
        if ($last) {
            $this->_nextButton->setAttrib('disabled', 'disabled');
        } else {
            $this->_nextButton->setAttrib('disabled', null);
        }
    }

    /**
     * Add the previous button
     */
    protected function addPreviousButton()
    {
        $class = 'MUtil_Form_Element_FakeSubmit';
        $this->_addButton(
                $this->_previousButton,
                $this->previousButtonId,
                $this->previousLabel,
                $this->_('< Previous'),
                $class
                );
        if (1 == $this->currentStep) {
            $this->_previousButton->setAttrib('disabled', 'disabled');
        } else {
            $this->_previousButton->setAttrib('disabled', null);
        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    abstract protected function addStepElementsFor(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, $step);

    /**
     * Add items in hidden form to the bridge, and remove them from the items array
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param string $element1
     *
     * @return void
     */
    protected function addItemsHidden(MUtil_Model_FormBridge $bridge, $element1)
    {
        $args = func_get_args();
        if (count($args)<2) {
            throw new Gems_Exception_Coding('Use at least 2 arguments, first the bridge and then one or more individual items');
        }

        $bridge   = array_shift($args);
        $elements = MUtil_Ra::flatten($args);
        $form     = $bridge->getForm();

        //Remove the elements from the _items variable
        $this->_items = array_diff($this->_items, $elements);

        //And add them to the bridge
        foreach($elements as $name) {
            // Do not use $bridge->addHidden as that adds validators and filters.
            $element = new Zend_Form_Element_Hidden($name);

            $bridge->addElement($element);
        }
    }

    /**
     * Creates from the model a Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @param int $step The current step
     * @return Zend_Form
     */
    protected function getModelFormFor($step)
    {
        $model    = $this->getModel();
        $baseform = $this->createForm();
        $bridge   = new MUtil_Model_FormBridge($model, $baseform);

        $this->_items = null;
        $this->initItems();

        $this->addFormElementsFor($bridge, $model, $step);

        return $bridge->getForm();
    }

    /**
     * The number of steps in this form
     *
     * @return int
     */
    abstract protected function getStepCount();

    /**
     * Makes sure there is a form.
     *
     * @param int $step The current step
     */
    protected function loadFormFor($step)
    {
        if (! isset($this->_forms[$step])) {
            $this->_forms[$step] = $this->getModelFormFor($step);
        }
        $this->_form = $this->_forms[$step];
        $this->addButtons();
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
        if (isset($this->formData[$this->stepFieldName])) {
            $this->currentStep = $this->formData[$this->stepFieldName];
        } else {
            $this->formData[$this->stepFieldName] = $this->currentStep;
        }

        // Make sure there is a $this->_form
        $this->loadFormFor($this->currentStep);

        if ($this->request->isPost()) {
            //First populate the form, otherwise the buttons will never be 'checked'!
            $this->populateForm();

            if ($this->_previousButton && $this->_previousButton->isChecked()) {
                $this->currentStep = $this->currentStep - 1;
                $this->loadFormFor($this->currentStep);
                $this->formData[$this->stepFieldName] = $this->currentStep;
                $this->populateForm();

            } else {
                if ($this->validateForm()) {

                    // if ($this->_nextButton && $this->_nextButton->isChecked()) {
                    if ($this->_nextButton && isset($this->formData[$this->nextButtonId]) && $this->formData[$this->nextButtonId]) {
                        $this->currentStep = $this->currentStep + 1;
                        $this->loadFormFor($this->currentStep);
                        $this->formData[$this->stepFieldName] = $this->currentStep;
                        $this->populateForm();

                    } else  {
                        /*
                         * Now that we validated, the form is populated. But I think the step
                         * below is not needed as the values in the form come from the data array
                         * but performing a getValues() cleans the data array so data in post but
                         * not in the form is removed from the data variable.
                         */
                        $this->formData = $this->_form->getValues();

                        MUtil_Echo::track($this->request->getPost(), $this->_nextButton->isChecked());
                        // Save
                        // $this->saveData();

                        // Reroute (always, override function otherwise)
                        // $this->setAfterSaveRoute();
                    }
                } else {
                    $this->onInValid();
                }
            }
        }

        return ! $this->getRedirectRoute();
    }
}
