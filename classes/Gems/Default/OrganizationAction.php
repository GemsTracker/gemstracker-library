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
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package Gems
 * @subpackage Default
 */

/**
 *
 * @author Matijs de Jong
 * @package Gems
 * @subpackage Default
 */
class Gems_Default_OrganizationAction  extends Gems_Controller_BrowseEditAction
{
    public $autoFilter = false;

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

            if (Gems_Cookies::set('organization', $orgId)) {
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
        $model->set(
            'gor_active', 'label', $this->_('Active'), 'elementClass', 'Checkbox',
            'multiOptions', $this->util->getTranslated()->getYesNo()
        );

        if ($detailed) {
            $model->set('gor_name',      'validator', $model->createUniqueValidator('gor_name'));
            $model->set('gor_welcome',   'label', $this->_('Greeting'),  'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
            $model->set('gor_signature', 'label', $this->_('Signature'), 'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        }

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
