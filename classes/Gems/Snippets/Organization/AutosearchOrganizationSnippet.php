<?php

/**
 * Copyright (c) 2015, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
