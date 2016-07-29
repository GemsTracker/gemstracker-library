<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Snippets_Agenda_CalendarSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

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

        $orgs = $this->currentUser->getRespondentOrganizations();
        if (count($orgs) > 1) {
            $elements[] = $this->_createSelectElement('gap_id_organization', $orgs, $this->_('(all organizations)'));
        }

        $locations = $this->loader->getAgenda()->getLocations();
        if (count($locations) > 1) {
            $elements[] = $this->_createSelectElement('gap_id_location', $locations, $this->_('(all locations)'));
        }

        $elements[] = null;

        $this->_addPeriodSelectors($elements, 'gap_admission_time');

        return $elements;
    }
}
