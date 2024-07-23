<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Location
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Location;

/**
 * @package    Gems
 * @subpackage Snippets\Location
 * @since      Class available since version 1.0
 */
class LocationSearchFormSnippet extends \Gems_Snippets_AutosearchFormSnippet
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

        $elements[] = $this->_createSelectElement('glo_active', $this->model, $this->_('(all active)'));
        $elements[] = $this->_createSelectElement('glo_filter', $this->model, $this->_('(all filtered)'));

        return $elements;
    }
}