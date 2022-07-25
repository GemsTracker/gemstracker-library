<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Fields;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class FieldsAutosearchForm extends \Gems\Snippets\AutosearchFormSnippet
{
    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of \Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(array $data)
    {
        $elements = parent::getAutoSearchElements($data);

        $elements[] = new \Zend_Form_Element_Hidden(\MUtil\Model::REQUEST_ID);
        $elements[] = $this->_createSelectElement('gtf_field_type', $this->model, $this->_('(all types)'));
        $elements[] = \MUtil\Html::create('br');

        return $elements;
    }
    
    /**
     * Return the fixed parameters
     * 
     * Normally these are the hidden parameters like ID
     * 
     * @return array
     */
    protected function getFixedParams()
    {
        $neededParams = parent::getFixedParams();
        
        $neededParams[] = \MUtil\Model::REQUEST_ID;
        
        return $neededParams;
        
    }
}
