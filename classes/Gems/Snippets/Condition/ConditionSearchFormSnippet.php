<?php
                
/**
 *
 * @package    Gem
 * @subpackage Snippets\Condition
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Snippets\Condition;

/**
 *
 * @package    Gem
 * @subpackage Snippets\Condition
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.8
 */
class ConditionSearchFormSnippet extends \Gems\Snippets\AutosearchFormSnippet
{
    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

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
        $conditons = $this->loader->getConditions();

        $elements = parent::getAutoSearchElements($data);

        $elements['gcon_type']   = $this->_createSelectElement('gcon_type',  $this->model, $this->_('(all types)'));
        $elements['gcon_active'] = $this->_createSelectElement('gcon_active', $this->model, $this->_('(any active)'));

        return $elements;
    }

}