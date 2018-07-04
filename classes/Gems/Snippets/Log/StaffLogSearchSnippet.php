<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Log
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: StaffLogSearchSnippet.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Snippets\Log;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Log
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 7-mei-2015 17:54:58
 */
class StaffLogSearchSnippet extends LogSearchSnippet
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
