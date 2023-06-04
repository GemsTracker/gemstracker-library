<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Generic
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 21-apr-2015 13:28:39
 */
class AutosearchInRespondentSnippet extends \Gems\Snippets\AutosearchPeriodFormSnippet
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
        $elements[] = new \Zend_Form_Element_Hidden(\MUtil\Model::REQUEST_ID1);
        $elements[] = new \Zend_Form_Element_Hidden(\MUtil\Model::REQUEST_ID2);

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
        
        $neededParams[] = \MUtil\Model::REQUEST_ID1;
        $neededParams[] = \MUtil\Model::REQUEST_ID2;
        
        return $neededParams;
        
    }
}
