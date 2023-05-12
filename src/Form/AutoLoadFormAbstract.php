<?php

/**
 *
 * @package    Gems
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Form;

/**
 * Adds default element loading to standard form
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
abstract class AutoLoadFormAbstract extends \Gems\Form
{
    /**
     * The field name for the submit element.
     *
     * @var string
     */
    protected $_submitFieldName = 'button';

    /**
     * When true all elements are loaded after initiation.
     *
     * @var boolean
     */
    protected $loadDefault = true;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->loadDefault) {
            $this->loadDefaultElements();
        }
    }

    /**
     * When true all elements are loaded after initiation.
     *
     * @return boolean $loadDefault
     */
    public function getLoadDefault($loadDefault = true)
    {
        return $this->loadDefault;
    }

    /**
     * Returns/sets a submit button.
     *
     * @return \Zend_Form_Element_Submit
     */
    public function getSubmitButton()
    {
        $element = $this->getElement($this->_submitFieldName);

        if (! $element) {
            // Submit knop
            $options = array('class' => 'button');

            $element = $this->createElement('submit', $this->_submitFieldName, $options);
            $element->setLabel($this->getSubmitButtonLabel());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    abstract public function getSubmitButtonLabel();

    /**
     * The function loads the elements for this form
     *
     * @return \Gems\Form\AutoLoadFormAbstract (continuation pattern)
     */
    abstract public function loadDefaultElements();

    /**
     * When true all elements are loaded after initiation.
     *
     * Enables loading of parameter through \Zend_Form::__construct()
     *
     * @param boolean $loadDefault
     * @return \Gems\User\Form\LoginForm (continuation pattern)
     */
    public function setLoadDefault($loadDefault = true)
    {
        $this->loadDefault = $loadDefault;

        return $this;
    }
}
