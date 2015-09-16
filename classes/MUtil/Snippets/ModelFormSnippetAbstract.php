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
abstract class MUtil_Snippets_ModelFormSnippetAbstract extends \MUtil_Snippets_ModelSnippetAbstract
{
    /**
     * Optional csrf element
     *
     * @var \Zend_Form_Element_Hash
     */
    protected $_csrf;

    /**
     *
     * @var \Zend_Form
     */
    protected $_form;

    /**
     * Array of item names still to be added to the form
     *
     * @var array
     */
    protected $_items;

    /**
     *
     * @var \Zend_Form_Element_Submit
     */
    protected $_saveButton;

    /**
     *
     * @var boolean When true the item key fields are added to the after save route url
     */
    protected $afterSaveRouteKeys = true;

    /**
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @var mixed Nothing or either an array or a string that is acceptable for Redirector->gotoRoute()
     */
    protected $afterSaveRouteUrl;

    /**
     *
     * @var string class attribute for buttons
     */
    protected $buttonClass = 'button btn btn-sm btn-primary';

    /**
     * True when the form should edit a new model item.
     *
     * @var boolean
     */
    protected $createData = false;

    /**
     * Field id for crsf protection field.
     *
     * @var string
     */
    protected $csrfId = 'no_csrf';

    /**
     * The timeout for crsf, 300 is default
     *
     * @var int
     */
    protected $csrfTimeout = 300;

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
     * Output only those elements actually used by the form.
     *
     * When false all fields without a label or elementClass are hidden,
     * when true those are left out, unless they happend to be a key field or
     * needed for a dependency.
     *
     * @var boolean
     */
    protected $onlyUsedElements = false;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
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
     * If empty save button is not added
     *
     * @var string
     */
    protected $saveButtonId = 'save_button';

    /**
     * The save button label (default is translated 'Save')
     *
     * @var string
     */
    protected $saveLabel = null;

    /**
     * Use csrf token on form for protection against Cross Site Request Forgery
     *
     * @var boolean
     */
    public $useCsrf = true;

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addCsrf()
    {
        if (! $this->_csrf) {
            $this->_form->addElement('hash', $this->csrfId, array(
                'salt' => 'mutil_' . $this->request->getControllerName() . '_' . $this->request->getActionName(),
                'timeout' => $this->csrfTimeout,
                ));
            $this->_csrf = $this->_form->getElement($this->csrfId);
        }

        return $this;
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        //Get all elements in the model if not already done
        $this->initItems();

        //And any remaining item
        $this->addItems($bridge, $this->_items);
    }

    /**
     * Add items to the bridge, and remove them from the items array
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param string $element1
     *
     * @return void
     */
    protected function addItems(\MUtil_Model_Bridge_FormBridgeInterface $bridge, $element1)
    {
        $args = func_get_args();
        if (count($args)<2) {
            throw new \Gems_Exception_Coding('Use at least 2 arguments, first the bridge and then one or more individual items');
        }

        array_shift($args); // Remove bridge
        $elements = \MUtil_Ra::flatten($args);
        $model    = $this->getModel();

        //Remove the elements from the _items variable
        $this->_items = array_diff($this->_items, $elements);

        //And add them to the bridge
        foreach($elements as $name) {
            if ($model->has($name, 'label') || $model->has($name, 'elementClass')) {
                $bridge->add($name);
            } else {
                $bridge->addHidden($name);
            }
        }
    }

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

                $options = array('label' => $this->saveLabel);
                if ($this->buttonClass) {
                    $options['class'] = $this->buttonClass;
                }

                $this->_saveButton = $this->_form->createElement('submit', $this->saveButtonId, $options);

                $this->_form->addElement($this->_saveButton);
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
     */
    protected function beforeDisplay()
    { }

    /**
     * Perform some actions to the data before it is saved to the database
     */
    protected function beforeSave()
    { }

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
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $form = new \MUtil_Form($options);
        if (MUtil_Bootstrap::enabled()) {
            $form->activateBootstrap();
        }

        return $form;
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
        // Again, just to be sure all changes are set on the form
        $this->populateForm();

        // Hook for subclasses
        $this->beforeDisplay();

        if ($this->_csrf) {
            $this->_csrf->initCsrfToken();
        }

        return $this->_form;
    }

    /**
     * Creates from the model a \Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @return \Zend_Form
     */
    protected function getModelForm()
    {
        $model    = $this->getModel();
        $baseform = $this->createForm();
        $bridge   = $model->getBridgeFor('form', $baseform);

        $this->addFormElements($bridge, $model);

        return $bridge->getForm();
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
     * {@see \MUtil_Registry_TargetInterface}.
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
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $model        = $this->getModel();
            $this->_items = $model->getItemsOrdered();

            if ($this->onlyUsedElements) {
                $model->clearElementClasses();
            }
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
        $model = $this->getModel();

        if ($this->request->isPost()) {
            // 1 - When posting, posted data is used as a value first
            // 2 - Then we use any values already set
            $formData = array($this->request->getPost() + $this->formData);

            $formData = $model->processAfterLoad($formData, $this->createData, true);
            
            // Get the first row
            $this->formData = reset($formData);
            
            // Now load data from the model for possibly missing values. Since model
            // loads them, we don't need a manual processAfterLoad anymore.
            if ($this->createData) {
                $modelData = $model->loadNew();
            } else {
                $modelData = $model->loadFirst();
            }            
            $this->formData = $this->formData + $modelData;

        } else {
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                if ($this->createData) {
                    $this->formData = $model->loadNew();
                } else {
                    $this->formData = $model->loadFirst();

                    if (! $this->formData) {
                        throw new \Zend_Exception($this->_('Unknown edit data requested'));
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

        if ($this->_csrf) {
            if ($this->_csrf->getMessages()) {
                $this->addMessage($this->_('The form was open for too long or was opened in multiple windows.'));
            }
        }
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

        // Use Csrf when enabled
        if ($this->useCsrf) {
            $this->addCsrf();
        }

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
        $this->beforeSave();

        if ($this->csrfId && $this->_csrf) {
            unset($this->formData[$this->csrfId]);
        }

        // Perform the save
        $model          = $this->getModel();
        $this->formData = $model->save($this->formData);
        $changed        = $model->getChanged();

        // Message the save
        $this->afterSave($changed);
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array($this->request->getActionKey() => $this->routeAction);

            if ($this->afterSaveRouteKeys) {
                // Set the key identifiers for the route.
                //
                // Mind you the values may have changed, either because of an edit or
                // because a new item was created.
                foreach ($this->getModel()->getKeys() as $id => $key) {
                    if (isset($this->formData[$key])) {
                        $this->afterSaveRouteUrl[$id] = $this->formData[$key];
                    }
                }
            }
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
        // Note we use an \MUtil_Form
        return $this->_form->isValid($this->formData, $this->disableValidatorTranslation);
    }
}