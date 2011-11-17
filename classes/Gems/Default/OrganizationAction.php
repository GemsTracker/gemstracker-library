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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Generic controller class for showing and editing organizations
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_OrganizationAction extends Gems_Controller_BrowseEditAction // Gems_Controller_ModelSnippetActionAbstract
{
    public $autoFilter = false;

    public function afterSave(array $data, $isNew)
    {
        $org = $this->loader->getOrganization($data['gor_id_organization']);
        $org->invalidateCache();
        return parent::afterSave($data, $isNew);
    }

    public function changeUiAction()
    {
        $request    = $this->getRequest();
        $org        = urldecode($request->getParam('org'));
        $url        = base64_decode($request->getParam('current_uri'));
        $oldOrgId   = $this->session->user_organization_id;

        $allowedOrganizations = $this->loader->getCurrentUser()->getAllowedOrganizations();
        if ($orgId = array_search($org, $allowedOrganizations)) {
            $this->session->user_organization_id = $orgId;
            $this->session->user_organization_name = $allowedOrganizations[$orgId];

            if ($this->escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
                $this->session->user_style = $this->db->fetchOne(
                    "SELECT gor_style
                        FROM gems__organizations
                        WHERE gor_id_organization = ?", $orgId
                );
            }

            //Now update the requestcache to change the oldOrgId to the new orgId
            //Don't do it when the oldOrgId doesn't match
            $requestCache = $this->session->requestCache;

            //Create the list of request cache keys that match an organization ID (to be extended)
            $possibleOrgIds = array(
                'gr2o_id_organization',
                'gto_id_organization');

            foreach ($requestCache as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $paramKey => $paramValue) {
                        if (in_array($paramKey, $possibleOrgIds)) {
                            if ($paramValue == $oldOrgId) {
                                $requestCache[$key][$paramKey] = $orgId;
                            }
                        }
                    }
                }
            }
            $this->session->requestCache = $requestCache;

            if (Gems_Cookies::setOrganization($orgId, $this->basepath->getBasePath())) {
                $this->getResponse()->setRedirect($url);
                return;
            }

            throw new Exception($this->_('Cookies must be enabled.'));
        }

        throw new Exception($this->_('Invalid organization.'));
    }


    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__organizations');

        $model->set('gor_name', 'label', $this->_('Name'), 'size', 25);
        $model->set('gor_location', 'label', $this->_('Location'), 'size', 25);
        $model->set('gor_url', 'label', $this->_('Url'), 'size', 50);
        $model->set('gor_task', 'label', $this->_('Task'), 'size', 25);
        $model->set('gor_contact_name', 'label', $this->_('Contact name'), 'size', 25);
        $model->set('gor_contact_email', 'label', $this->_('Contact email'), 'size', 50);
        if ($this->escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $model->setIfExists(
                'gor_style', 'label', $this->_('Style'),
                'multiOptions', MUtil_Lazy::call(array($this->escort, 'getStyles'))
            );
        }
        $model->set(
            'gor_iso_lang', 'label', $this->_('Language'),
            'multiOptions', $this->util->getLocalized()->getLanguages(), 'default', 'nl'
        );
        $yesNo = $this->util->getTranslated()->getYesNo();
        $model->set('gor_active', 'label', $this->_('Active'), 'elementClass', 'Checkbox', 'multiOptions', $yesNo);
        $model->set('gor_add_patients', 'label', $this->_('Allow new respondents'), 'elementClass', 'CheckBox', 'multiOptions', $yesNo);


        if ($detailed) {
            $model->set('gor_name',      'validator', $model->createUniqueValidator('gor_name'));
            $model->set('gor_welcome',   'label', $this->_('Greeting'),  'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
            $model->set('gor_signature', 'label', $this->_('Signature'), 'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        }
        $model->set('gor_accessible_by', 'label', $this->_('Accessible by'), 'description', $this->_('Checked organizations see this organizations respondents.'),
                'elementClass', 'MultiCheckbox', 'multiOptions', $this->util->getDbLookup()->getOrganizations());
        $tp = new MUtil_Model_Type_ConcatenatedRow(':', ', ');
        $tp->apply($model, 'gor_accessible_by');

        if ($this->project->multiLocale) {
            $model->set('gor_name', 'description', 'ENGLISH please! Use translation file to translate.');
            $model->set('gor_url',  'description', 'ENGLISH link preferred. Use translation file to translate.');
            $model->set('gor_task', 'description', 'ENGLISH please! Use translation file to translate.');
        }

        Gems_Model::setChangeFieldsByPrefix($model, 'gor');

        return $model;
    }

    public function getTopic($count = 1)
    {
        return $this->plural('organization', 'organizations', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Participating organizations');
    }
}
