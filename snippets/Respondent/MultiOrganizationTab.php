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
 * Displays tabs for multiple organizations.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Respondent_MultiOrganizationTab extends MUtil_Snippets_TabSnippetAbstract
{
    protected $href = array('page' => null);

    /**
     * Required
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

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

    protected function getParameterKey()
    {
        return 'id2';
    }

    protected function getTabs()
    {
        $user = $this->loader->getCurrentUser();
        $orgs = $user->getAllowedOrganizations();
        $sql  = "SELECT gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ? AND gr2o_id_organization = ?";
        $resp = $this->respondentData['grs_id_user'];

        // TODO:
        // Shows tabs only for existing respondents
        // Tabslinks should contain two parameters
        // No option for difference active / inactive
        
        foreach ($orgs as $orgId => $name) {
            if ($patientNr = $this->db->fetchOne($sql, array($resp, $orgId))) {
                $tabs[$orgId] = $name;
            }
        }

        return $tabs;
    }
}
