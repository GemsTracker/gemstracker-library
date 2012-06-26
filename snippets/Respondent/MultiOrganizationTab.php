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
 * @subpackage Snippets\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * Displays tabs for multiple organizations.
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Respondent_MultiOrganizationTab extends MUtil_Snippets_TabSnippetAbstract
{
    protected $href = array();

    /**
     * Required
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var array id specific hrefs
     */
    protected $hrefs;

    /**
     * Required
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var array
     */
    protected $respondentData;

    /**
     * Return the parameters that should be used for this tabId
     *
     * @param string $tabId
     * @return array
     */
    protected function getParameterKeysFor($tabId)
    {
        return $this->hrefs[$tabId];
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs()
    {
        $user = $this->loader->getCurrentUser();

        $sql  = "SELECT gr2o_id_organization, gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ?";

        $this->defaultTab = $user->getCurrentOrganizationId();
        $this->currentTab = $this->request->getParam(MUtil_Model::REQUEST_ID2);

        $allowedOrgs  = $user->getRespondentOrganizations();
        $existingOrgs = $this->db->fetchPairs($sql, $this->respondentData['grs_id_user']);

        foreach ($allowedOrgs as $orgId => $name) {
            if (isset($existingOrgs[$orgId])) {
                $tabs[$orgId] = $name;
                $this->hrefs[$orgId] = array(MUtil_Model::REQUEST_ID1 => $existingOrgs[$orgId], MUtil_Model::REQUEST_ID2 => $orgId);
            }
        }

        return $tabs;
    }
}
