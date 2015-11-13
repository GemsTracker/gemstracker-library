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
class Gems_Default_OrganizationAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'Organization\\OrganizationTableSnippet';

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
    protected $createEditSnippets = 'Organization\\OrganizationEditSnippet';

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * Switch the active organization
     */
    public function changeUiAction()
    {
        $request  = $this->getRequest();
        $orgId    = urldecode($request->getParam('org'));
        $oldOrg   = $this->currentUser->getCurrentOrganizationId();
        $origUrl  = base64_decode($request->getParam('current_uri'));

        $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        if (isset($allowedOrganizations[$orgId])) {
            $this->currentUser->setCurrentOrganization($orgId);

            if ($origUrl) {
                // Check for organisation id in url, but not when a patient id is stated
                if (strpos($origUrl, '/' . \MUtil_Model::REQUEST_ID1 . '/') === false) {
                    foreach ($this->currentUser->possibleOrgIds as $key) {
                        $finds[]    = '/' . $key. '/' . $oldOrg;
                        $replaces[] = '/' . $key. '/' . $orgId;
                    }
                    $correctUrl = str_replace($finds, $replaces, $origUrl);
                } else {
                    $correctUrl = $origUrl;
                }
                // \MUtil_Echo::track($origUrl, $correctUrl);
                $this->getResponse()->setRedirect($correctUrl);
            } else {
                $this->currentUser->gotoStartPage($this->menu, $request);
            }
            return;
        }

        throw new \Gems_Exception(
                $this->_('Inaccessible or unknown organization'),
                403, null,
                sprintf(
                        $this->_('Access to this page is not allowed for current role: %s.'),
                        $this->currentUser->getRole()
                        )
                );
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
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        if ($this->escort instanceof \Gems_Project_Layout_MultiLayoutInterface) {
            $styles = \MUtil_Lazy::call(array($this->escort, 'getStyles'));
        } else {
            $styles = array();
        }
        $model = $this->loader->getModels()->getOrganizationModel($styles);

        if ($detailed) {
            if (('create' == $action) || ('edit' == $action)) {
                $model->applyEditSettings();
            } else {
                $model->applyDetailSettings();
            }
        } else {
            $model->applyBrowseSettings();
        }

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
