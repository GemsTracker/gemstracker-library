<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Form_AskTokenForm extends \Gems_Form_AutoLoadFormAbstract
{
    /**
     * The field name for the token element.
     *
     * @var string
     */
    protected $_tokenFieldName = \MUtil_Model::REQUEST_ID;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    public function __construct($options = null)
    {
        if (isset($options['class'])) {
            $options['class'] .= ' ask-token-form';
        } else {
            $options['class'] = 'ask-token-form';
        }
        parent::__construct($options);
    }

    /**
     * Returns/sets a password element.
     *
     * @return \Zend_Form_Element_Password
     */
    public function getTokenElement()
    {
        $element = $this->getElement($this->_tokenFieldName);

        if (! $element) {
            $tokenLib   = $this->tracker->getTokenLibrary();
            $max_length = $tokenLib->getLength();

            // Veld token
            $element = new \Zend_Form_Element_Text($this->_tokenFieldName);
            $element->setLabel($this->translate->_('Token'));
            $element->setDescription(sprintf($this->translate->_('Enter tokens as %s.'), $tokenLib->getFormat()));
            $element->setAttrib('size', $max_length + 2);
            $element->setAttrib('maxlength', $max_length);
            $element->setRequired(true);
            $element->addFilter($this->tracker->getTokenFilter());
            $element->addValidator($this->tracker->getTokenValidator());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        return $this->translate->_('OK');
    }

    /**
     * The function loads the elements for this form
     *
     * @return \Gems_Form_AutoLoadFormAbstract (continuation pattern)
     */
    public function loadDefaultElements()
    {
        $this->getTokenElement();
        $this->getSubmitButton();

        return $this;
    }
}
