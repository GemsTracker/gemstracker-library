<?php

/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Form\Decorator;

/**
 * Takes care of rendering errors in tabbed forms
 *
 * $Id$
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class TabErrors extends \Zend_Form_Decorator_Abstract
{
    /**
     * By default, show verbose error messages in tabforms
     * 
     * @var boolean
     */
    protected $_verbose = true;

    /**
     * Render the TabErrors
     *
     * We don't return anything, we just add a class to the tab so it shows the errors
     *
     * @param <type> $content
     * @return <type>
     */
    public function render($content) {
        $form = $this->getElement();
        if (!$form instanceof \Zend_Form) {
            return $content;
        }

        $this->_recurseForm($form);

        return $content;
    }

    /**
     * Should the tab errors be verbose?
     * 
     * Verbose means that apart from marking and selecting the tab that has errors
     * we also show an error above the form.
     * 
     * @return boolean
     */
    public function getVerbose() {
        if (null !== ($verboseOpt = $this->getOption('verbose'))) {
            $this->_verbose = (bool) $verboseOpt;
            $this->removeOption('verbose');
        }

        return $this->_verbose;
    }

    /**
     * Recurse through a form object, rendering errors
     *
     * @param  \Zend_Form $form
     * @param  \Zend_View_Interface $view
     * @return string
     */
    protected function _recurseForm(\Zend_Form $form)
 {
        $subFormsWithErrors = array();
        $subFormMessages = array();
        $tabId = 0;

        foreach ($form->getSubForms() as $subForm) {
            if ($subForm instanceof \Gems\Form\TabSubForm) {
                // See if any of the subformelements has an error message
                foreach ($subForm->getElements() as $subFormElement) {
                    $elementMessages = $subFormElement->getMessages();
                    if (count($elementMessages)) {
                        $subFormsWithErrors[$tabId] = $subForm->getAttrib('title'); // Save subform title
                        $subForm->setAttrib('jQueryParams', array('class' => 'taberror'));    // Add css class to the subform
                        $form->selectTab($tabId);   // Select the tab, this way the last tab with error is always selected
                        break;  // don't check other elements
                    }
                }

                // Preserve subform level custom messages if we have an error
                if (array_key_exists($tabId, $subFormsWithErrors)) {
                    $subFormMessages[$tabId] = $subForm->getCustomMessages();
                }
                $tabId++;
            }
        }

        // If we found at least one error, and 'verbose' is true
        if ($this->getVerbose() && (!empty($subFormsWithErrors) || $form->isErrors())) {
            // First show form level custom error messages (the elements show their own errors)
            $formMessage = $form->getCustomMessages();
            if (!empty($formMessage)) {
                foreach ($formMessage as $message)
                {
                    \Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage($message);
                }
            }

            // Now browse through the tabs with errors
            foreach ($subFormsWithErrors as $tabIdx => $tabName)
            {
                // If more then one tab, show in which tab we found the errors
                if ($tabId > 1) {
                    $translator = \Zend_Registry::get('Zend_Translate');
                    \Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(sprintf($translator->_('Error in tab "%s"'), $tabName));
                }

                // If we have them, show the tab custom error messages
                foreach ($subFormMessages[$tabIdx] as $subFormMessage)
                {
                    foreach ($subFormMessage as $message)
                    {
                        \Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("--> " . $message);
                    }
                }
            }
        }
    }    
}