<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Staff;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Staff
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 28-sep-2015 12:19:23
 */
class StaffSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_Loader
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
        $elements = parent::getAutoSearchElements($data);

        if ($elements) {
            $optionsG = $this->util->getDbLookup()->getGroups();
            $elementG = $this->_createSelectElement('gsf_id_primary_group', $optionsG, $this->_('(all functions)'));
            $elements[] = $elementG;

            $user     = $this->loader->getCurrentUser();
            $optionsO = $user->getAllowedOrganizations();
            if (count($optionsO) > 1) {
                $elementO = $this->_createSelectElement('gsf_id_organization', $optionsO, $this->_('(all organizations)'));
                $elements[] = $elementO;
            }

            $optionsA = $this->model->get('gsf_active', 'multiOptions');
            $elementA = $this->_createSelectElement('gsf_active', $optionsA, $this->_('(both)'));
            $elementA->setLabel($this->model->get('gsf_active', 'label'));
            $elements[] = $elementA;

            $optionsT = $this->model->get('has_2factor', 'multiOptions');;
            $elementT = $this->_createSelectElement('has_2factor', $optionsT, $this->_('(all)'));
            $elementT->setLabel($this->model->get('has_2factor', 'label'));
            $elements[] = $elementT;
        }

        return $elements;
    }
}
