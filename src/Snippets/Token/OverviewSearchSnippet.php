<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Token;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Token
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 15-okt-2015 17:31:50
 */
class OverviewSearchSnippet extends PlanSearchSnippet
{
    /**
     * Display the period selector
     *
     * @var boolean
     */
    protected $periodSelector = false;

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

        // Has no function here
        unset($elements['token_status']);

        $elements[] = new \Zend_Form_Element_Hidden(\Gems\Selector\DateSelectorAbstract::DATE_FACTOR);
        $elements[] = new \Zend_Form_Element_Hidden(\Gems\Selector\DateSelectorAbstract::DATE_GROUP);
        $elements[] = new \Zend_Form_Element_Hidden(\Gems\Selector\DateSelectorAbstract::DATE_TYPE);

        return $elements;
    }
}
