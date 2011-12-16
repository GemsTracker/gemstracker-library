<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Organization_OrganizationEditSnippet extends Gems_Snippets_ModelTabFormSnippetGeneric
{
    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        //Get all elements in the model if not already done
        $this->initItems();

        //Create our tab structure first check if tab already exists to allow extension
        if (!($bridge->getTab('general'))) {
            $bridge->addTab('general', 'value', $this->_('General'));
        }
        //Need the first two together for validation
        $bridge->addHtml('org')->b($this->_('Organization'));
        $this->addItems($bridge, 'gor_name', 'gor_id_organization', 'gor_location', 'gor_url', 'gor_active');
        $bridge->addHtml('contact')->b($this->_('Contact'));
        $bridge->addHtml('contact_desc')->i($this->_('The contact details for this organization, used for emailing.'));
        $this->addItems($bridge, 'gor_contact_name', 'gor_contact_email');
        $bridge->addHtml('general_other')->b($this->_('Other'));
        $this->addItems($bridge, 'gor_iso_lang', 'gor_code', 'gor_has_respondents');

        if (!($bridge->getTab('email'))) {
            $bridge->addTab('email', 'value', $this->_('Email') . ' & ' . $this->_('Token'));
        }
        $this->addItems($bridge, 'gor_welcome', 'gor_signature');

        if (!($bridge->getTab('access'))) {
            $bridge->addTab('access', 'value', $this->_('Access'));
        }
        $this->addItems($bridge, 'gor_has_login', 'gor_add_respondents', 'gor_respondent_group', 'gor_accessible_by', 'gor_user_class');

        if (isset($this->formData['gor_user_class']) && !empty($this->formData['gor_user_class'])) {
            $class      = $this->formData['gor_user_class'] . 'Definition';
            $definition = $this->loader->getUserLoader()->getUserDefinition($class);

            if ($definition->hasConfig()) {
                $definition->appendConfigFields($bridge);
            }

        }

        //now add remaining items if any
        if (count($this->_items)>0 && !($bridge->getTab('other'))) {
            $bridge->addTab('other', 'value', $this->_('Other'));
        }
        parent::addFormElements($bridge, $model);
    }

    public function afterSave($changed)
    {
        $org = $this->loader->getOrganization($changed['gor_id_organization']);
        $org->invalidateCache();

        // Make sure any changes in the allowed list are reflected.
        $this->loader->getCurrentUser()->refreshAllowedOrganizations();

        return parent::afterSave($changed);
    }
}