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
class Gems_Default_OrganizationAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Organization_OrganizationTableSnippet';

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    public $cacheTags = array('organization', 'organizations');

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'Organization_OrganizationEditSnippet';

    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     * Switch the active organization
     */
    public function changeUiAction()
    {
        $user     = $this->loader->getCurrentUser();
        $request  = $this->getRequest();
        $orgId    = urldecode($request->getParam('org'));
        $oldOrg   = $user->getCurrentOrganizationId();
        $origUrl  = base64_decode($request->getParam('current_uri'));

        $allowedOrganizations = $user->getAllowedOrganizations();
        if (isset($allowedOrganizations[$orgId])) {
            $user->setCurrentOrganization($orgId);

            if ($origUrl) {
                // Check for organisation id in url, but not when a patient id is stated
                if (strpos($origUrl, '/' . MUtil_Model::REQUEST_ID1 . '/') === false) {
                    foreach ($user->possibleOrgIds as $key) {
                        $finds[]    = '/' . $key. '/' . $oldOrg;
                        $replaces[] = '/' . $key. '/' . $orgId;
                    }
                    $correctUrl = str_replace($finds, $replaces, $origUrl);
                } else {
                    $correctUrl = $origUrl;
                }
                // MUtil_Echo::track($origUrl, $correctUrl);
                $this->getResponse()->setRedirect($correctUrl);
            } else {
                $user->gotoStartPage($this->menu, $request);
            }
            return;
        }

        throw new Gems_Exception(
                $this->_('Inaccessible or unknown organization'),
                403, null,
                sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->loader->getCurrentUser()->getRole()));
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
        $model = $this->loader->getModels()->getOrganizationModel();

        $model->setDeleteValues('gor_active', 0, 'gor_add_respondents', 0);

        $model->set('gor_name', 'label', $this->_('Name'), 'size', 25);
        $model->set('gor_location', 'label', $this->_('Location'), 'size', 25);
        $model->set('gor_url', 'label', $this->_('Url'), 'size', 50);
        $model->set('gor_task', 'label', $this->_('Task'), 'size', 25);
        $model->set('gor_provider_id', 'label', $this->_('Healtcare provider id'),
                'description', $this->_('An interorganizational id used for import and export.'));
        $model->set('gor_contact_name', 'label', $this->_('Contact name'), 'size', 25);
        $model->set('gor_contact_email', 'label', $this->_('Contact email'), 'size', 50, 'validator', 'SimpleEmail');
        if ($this->escort instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $model->setIfExists('gor_style',
                'label', $this->_('Style'),
                'multiOptions', MUtil_Lazy::call(array($this->escort, 'getStyles'))
            );
        }
        $model->setIfExists('gor_url_base',
            'label', $this->_("Default url's"),
            'size', 50,
            'description', sprintf($this->_("Always switch to this organization when %s is accessed from one of these space separated url's. The first is used for mails."), $this->project->getName())
        );
        if ($detailed) {
            $model->setIfExists('gor_url_base', 'filter', 'TrailingSlash');
        }
        $model->set(
            'gor_iso_lang', 'label', $this->_('Language'),
            'multiOptions', $this->util->getLocalized()->getLanguages(), 'default', 'nl'
        );
        $yesNo = $this->util->getTranslated()->getYesNo();
        $model->set('gor_active', 'label', $this->_('Active'), 'description', $this->_('Can the organization be used?'), 'elementClass', 'Checkbox', 'multiOptions', $yesNo);
        $model->set('gor_has_login', 'label', $this->_('Login'), 'description', $this->_('Can people login for this organization?'), 'elementClass', 'CheckBox', 'multiOptions', $yesNo);
        $model->set('gor_add_respondents', 'label', $this->_('Accepting'), 'description', $this->_('Can new respondents be added to the organization?'), 'elementClass', 'CheckBox', 'multiOptions', $yesNo);
        $model->set('gor_has_respondents', 'label', $this->_('Respondents'), 'description', $this->_('Does the organization have respondents?'), 'elementClass', 'Exhibitor', 'multiOptions', $yesNo);
        $model->set('gor_respondent_group', 'label', $this->_('Respondent group'), 'description', $this->_('Allows respondents to login.'), 'multiOptions', $this->util->getDbLookup()->getAllowedRespondentGroups());

        if ($detailed) {
            $model->set('gor_name',      'validator', $model->createUniqueValidator('gor_name'));
            $model->set('gor_welcome',   'label', $this->_('Greeting'),  'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
            $model->set('gor_signature', 'label', $this->_('Signature'), 'description', $this->_('For emails and token forward screen.'), 'elementClass', 'Textarea', 'rows', 5);
        }
        $model->set('gor_accessible_by', 'label', $this->_('Accessible by'), 'description', $this->_('Checked organizations see this organizations respondents.'),
                'elementClass', 'MultiCheckbox', 'multiOptions', $this->util->getDbLookup()->getOrganizations());
        $tp = new MUtil_Model_Type_ConcatenatedRow(':', ', ');
        $tp->apply($model, 'gor_accessible_by');

        if ($detailed && $this->project->multiLocale) {
            $model->set('gor_name', 'description', 'ENGLISH please! Use translation file to translate.');
            $model->set('gor_url',  'description', 'ENGLISH link preferred. Use translation file to translate.');
            $model->set('gor_task', 'description', 'ENGLISH please! Use translation file to translate.');
        }
        $model->setIfExists('gor_code', 'label', $this->_('Code name'), 'size', 10, 'description', $this->_('Only for programmers.'));

        $model->setIfExists('gor_allowed_ip_ranges',
            'label', $this->_('Allowed IP Ranges'),
            'description', $this->_('Separate with | example: 10.0.0.0-10.0.0.255 (subnet masks are not supported)'),
            'size', 50,
            'validator', new Gems_Validate_IPRanges(),
            'maxlength', 500
            );

        if($model->has('gor_user_class')) {
            $definitions = $this->loader->getUserLoader()->getAvailableStaffDefinitions();
            //Use first element as default
            $tmp = array_keys($definitions);
            $default = array_shift($tmp);
            $model->set('gor_user_class', 'default', $default);
            if (count($definitions)>1) {
                if ($action !== 'create') {
                    $model->set('gor_user_class', 'elementClass', 'Exhibitor', 'description', $this->_('This can not be changed yet'));
                }
                $model->set('gor_user_class', 'label', $this->_('User Definition'), 'multiOptions', $definitions);
            } else {
                $model->set('elementClass', 'hidden');
            }
        }

        $model->addColumn("CASE WHEN gor_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        Gems_Model::setChangeFieldsByPrefix($model, 'gor');

        return $model;
    }

    public function getEditTitle()
    {
        $data = $this->getModel()->loadFirst();

        //Add location to the subject
        $subject = sprintf('%s - %s', $data['gor_name'], $data['gor_location']);

        return sprintf($this->_('Edit %s %s'), $this->getTopic(1), $subject);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Participating organizations');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('organization', 'organizations', $count);
    }
}
