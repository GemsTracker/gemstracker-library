<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AutosearchWithIdSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
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
class AutosearchWithIdSnippet extends \Gems_Snippets_AutosearchFormSnippet
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
        $elements[] = new \Zend_Form_Element_Hidden(\MUtil_Model::REQUEST_ID);

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
        
        $neededParams[] = \MUtil_Model::REQUEST_ID;
        
        return $neededParams;
        
    }
}
