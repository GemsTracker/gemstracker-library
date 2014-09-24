<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: GroupDeleteSnippet.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 24-sep-2014 18:26:00
 */
class Gems_Snippets_Group_GroupDeleteSnippet extends Gems_Snippets_ModelItemYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $model = $this->getModel();
        $data  = $model->loadFirst();
        $user  = $this->loader->getCurrentUser();
        $roles = $user->getAllowedRoles();

        //MUtil_Echo::track($data);

        // Perform access check here, before anything has happened!!!
        if (isset($data['ggp_role']) && (! isset($roles[$data['ggp_role']]))) {
            $this->addMessage($this->_('You do not have sufficient privilege to edit this group.'));
            $this->afterSaveRouteUrl = array($this->request->getActionKey() => 'show');
            $this->resetRoute        = false;

            return false;
        }
        $this->menu->getParameterSource()->offsetSet('ggp_role', $data['ggp_role']);

        return parent::hasHtmlOutput();
    }
}
