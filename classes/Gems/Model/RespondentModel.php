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
 * @version    $Id: RespondentModel.php 345 2011-07-28 08:39:24Z 175780 $
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

        $this->setSaveWhenNotNull('grs_bsn');
        $this->setOnSave('grs_bsn', array($this, 'formatBSN'));
    }

    public function formatBSN($name, $value, $new = false)
    {
        return md5($value);
    }

    public function addPhysicians()
    {
        $this->addLeftTable('gems__staff', array('gr2o_id_physician' => 'gsf_id_user'));
        return $this;
    }

    public function copyKeys($reset = false)
    {
        $keys = $this->_getKeysFor('gems__respondent2org');
        $key = reset($keys);

        $this->addColumn('gems__respondent2org.' . $key, $this->getKeyCopyName($key));

        return $this;
    }

    public function getSelect()
    {
        $select = parent::getSelect();
        $adapter = $select->getAdapter();

        $select->where($adapter->quoteIdentifier('gr2o_id_organization') . ' = ?', $this->getCurrentOrganization());

        return $select;
    }

    public function getRespondentTracksModel()
    {
        $model = new Gems_Model_JoinModel('surveys', 'gems__respondent2track');
        $model->addTable('gems__tracks', array('gr2t_id_track' => 'gtr_id_track'));
        $model->addTable('gems__respondent2org', array('gr2t_id_user' => 'gr2o_id_user'));

        return $model;
    }

    public function save(array $newValues, array $filter = null, array $saveTables = null)
    {
        if ((null === $filter) || (! array_key_exists('gr2o_id_organization', $filter))) {
            $filter['gr2o_id_organization'] = $this->getCurrentOrganization();
        }

        if (! (isset($newValues['gr2o_id_organization']) && $newValues['gr2o_id_organization'])) {
            $newValues['gr2o_id_organization'] = $filter['gr2o_id_organization'];
        }

        return parent::save($newValues, $filter, $saveTables);
    }
}

