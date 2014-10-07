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
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OrganizationEditSnippet.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
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
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
         parent::afterSave($changed);

        // Make sure any changes in the allowed list are reflected.
        $this->loader->getCurrentUser()->refreshAllowedOrganizations();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        if (isset($this->formData['gor_id_organization']) && $this->formData['gor_id_organization']) {
            $model = $this->getModel();

            // Strip self from list of organizations
            $multiOptions = $model->get('gor_accessible_by', 'multiOptions');
            unset($multiOptions[$this->formData['gor_id_organization']]);
            $model->set('gor_accessible_by', 'multiOptions', $multiOptions);

            // Show allowed organisations
            $org         = $this->loader->getOrganization($this->formData['gor_id_organization']);
            $allowedOrgs = $org->getAllowedOrganizations();
            //Strip self
            unset($allowedOrgs[$this->formData['gor_id_organization']]);
            $display = join(', ', $allowedOrgs);
            if (! $display) {
                $display = MUtil_Html::create('em', $this->_('No access to other organizations.'));
            }

            $model->set('allowed', 'value', $display);
        }
    }
}