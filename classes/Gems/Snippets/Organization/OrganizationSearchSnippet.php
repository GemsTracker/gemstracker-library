<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Organization;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 9, 2017 2:44:24 PM
 */
class OrganizationSearchSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \gems_User_User
     */
    protected $currentUser;

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
        // Search text
        $elements = parent::getAutoSearchElements($data);

        $respondentList[''] = $this->_('(all organizations)');
        $respondentList['maybePatient'] = $this->_('Organizations that may have respondents');
        $respondentList['createPatient'] = $this->_('Organizations that create respondents');
        $respondentList['hasPatient'] = $this->_('Organizations that have respondents');
        $respondentList['noNewPatient'] = $this->_('Organizations that cannot create new respondents');
        $respondentList['noPatient'] = $this->_('Organizations that have no respondents');

        $elements['pats'] = $this->_createSelectElement('respondentstatus', $respondentList);
        $elements['orgs'] = $this->_createSelectElement('accessible_by', $this->currentUser->getAllowedOrganizations(), '(accessible by any organization)');

        return $elements;
    }

}
