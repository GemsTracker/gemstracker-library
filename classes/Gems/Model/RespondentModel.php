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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard Respondent model.
 *
 * When a project defines its own sub-class of this class and names
 * it <Project_name>_Model_RespondentModel, that class is loaded
 * instead.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_RespondentModel extends Gems_Model_HiddenOrganizationModel
{
    const SSN_HASH = 0;
    const SSN_HIDE = 1;
    const SSN_OPEN = 2;

    /**
     * Determines how the social security number is stored.
     *
     * Can be changed is derived classes.
     *
     * @var int One of the SSN_ constants
     */
    public $hashSsn = self::SSN_HASH;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    public function __construct()
    {
        // gems__respondents MUST be first table for INSERTS!!
        parent::__construct('respondents', 'gems__respondents', 'grs');

        $this->addTable('gems__respondent2org', array('grs_id_user' => 'gr2o_id_user'), 'gr2o');
        $this->addTable('gems__reception_codes', array('gr2o_reception_code' => 'grc_id_reception_code'));

        $this->setKeys($this->_getKeysFor('gems__respondent2org'));

        $this->setOnSave('gr2o_opened', new Zend_Db_Expr('CURRENT_TIMESTAMP'));
        $this->setSaveOnChange('gr2o_opened');
        $this->setOnSave('gr2o_opened_by', GemsEscort::getInstance()->session->user_id);
        $this->setSaveOnChange('gr2o_opened_by');

        if (self::SSN_HASH === $this->hashSsn) {
            $this->setSaveWhenNotNull('grs_ssn');
            $this->setOnSave('grs_ssn', array($this, 'formatSSN'));
        }
    }

    /**
     * Add an organization filter if it wasn't specified in the filter.
     *
     * Checks the filter on sematic correctness and replaces the text seacrh filter
     * with the real filter.
     *
     * @param mixed $filter True for the filter stored in this model or a filter array
     * @return array The filter to use
     */
    protected function _checkFilterUsed($filter)
    {
        $filter = parent::_checkFilterUsed($filter);

        if (! isset($filter['gr2o_id_organization'])) {
            if ($this->isMultiOrganization() && !isset($filter['gr2o_patient_nr'])) {
                // If we are not looking for a specific patient, we can look at all patients
                $filter[] = 'gr2o_id_organization IN (' . implode(', ', array_keys($this->user->getAllowedOrganizations())) . ')';
            } else {
                // Otherwise, we can only see in our current organization
                $filter['gr2o_id_organization'] = $this->getCurrentOrganization();
            }
        }

        if (self::SSN_HASH === $this->hashSsn) {
            // Make sure a search for a SSN is hashed when needed.
            array_walk_recursive($filter, array($this, 'applyHash'));
        }

        return $filter;
    }

    /**
     * Add the table and field to check for respondent login checks
     *
     * @return Gems_Model_RespondentModel (continuation pattern)
     */
    public function addLoginCheck()
    {
        $this->addLeftTable(
                'gems__user_logins',
                array('gr2o_patient_nr' => 'gul_login', 'gr2o_id_organization' => 'gul_id_organization'),
                'gul',
                MUtil_Model_DatabaseModelAbstract::SAVE_MODE_UPDATE |
                    MUtil_Model_DatabaseModelAbstract::SAVE_MODE_DELETE);

        $this->addColumn(
                "CASE WHEN gul_id_user IS NULL OR gul_user_class = 'NoLogin' OR gul_can_login = 0 THEN 0 ELSE 1 END",
                'has_login');

        return $this;
    }

    /**
     * Apply hash function for array_walk_recursive in _checkFilterUsed()
     *
     * @see _checkFilterUsed()
     *
     * @param string $filterValue
     * @param string $filterKey
     */
    public function applyHash(&$filterValue, $filterKey)
    {
        if ('grs_ssn' === $filterKey) {
            $filterValue = $this->project->getValueHash($filterValue);
        }
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function formatSSN($value, $isNew = false, $name = null, array $context = array())
    {
        if ($value) {
            return $this->project->getValueHash($value);
        }
    }

    public function copyKeys($reset = false)
    {
        $keys = $this->_getKeysFor('gems__respondent2org');

        foreach ($keys as $key) {
            $this->addColumn('gems__respondent2org.' . $key, $this->getKeyCopyName($key));
        }

        return $this;
    }

    public function getRespondentTracksModel()
    {
        $model = new Gems_Model_JoinModel('surveys', 'gems__respondent2track');
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__respondent2org', array('gr2t_id_user' => 'gr2o_id_user'));

        return $model;
    }

    /**
     * True when the default filter can contain multiple organizations
     *
     * @return boolean
     */
    public function isMultiOrganization()
    {
        // return ($this->user->hasPrivilege('pr.respondent.multiorg') && (! $this->user->getCurrentOrganization()->canHaveRespondents()));
        return $this->user->hasPrivilege('pr.respondent.multiorg');
    }
}

