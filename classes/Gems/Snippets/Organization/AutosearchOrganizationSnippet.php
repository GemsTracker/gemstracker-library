<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AutosearchOrganizationSnippet.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Organization;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 11-mei-2015 18:35:49
 */
class AutosearchOrganizationSnippet extends \Gems_Snippets_AutosearchFormSnippet
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var string The field that contains an organization id
     */
    protected $organizationField;

    /**
     *
     * @var boolean When true show only respondent organizations
     */
    protected $respondentOrganizations = false;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

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

        if ($this->organizationField) {
            $user = $this->loader->getCurrentUser();

            if ($this->respondentOrganizations) {
                $availableOrganizations = $this->util->getDbLookup()->getOrganizationsWithRespondents();
            } else {
                $availableOrganizations = $this->util->getDbLookup()->getActiveOrganizations();
            }

            if ($user->hasPrivilege('pr.staff.see.all')) {
                // Select organization
                $options = $availableOrganizations;
            } else {
                $options = array_intersect($availableOrganizations, $user->getAllowedOrganizations());
            }

            if ($options) {
                $elements[] = $this->_createSelectElement(
                        $this->organizationField,
                        $options,
                        $this->_('(all organizations)')
                        );
            }
        }

        return $elements;
    }
}
