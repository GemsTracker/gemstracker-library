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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
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
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LogModel.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

namespace Gems\Model;

use MUtil\Model\Type\JsonData;

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 16:53:36
 */
class LogModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Create a model for the log
     */
    public function __construct()
    {
        parent::__construct('Log', 'gems__log_activity', 'gla', true);
        $this->addTable('gems__log_setup', array('gla_action' => 'gls_id_action'))
                ->addLeftTable('gems__respondents', array('gla_respondent_id' => 'grs_id_user'))
                ->addLeftTable('gems__staff', array('gla_by' => 'gsf_id_user'));

        $this->addColumn(new \Zend_Db_Expr(
                "CONCAT(COALESCE(gsf_last_name, '-'), ', ', COALESCE(CONCAT(gsf_first_name, ' '), ''), COALESCE(gsf_surname_prefix, ''))"
                ), 'staff_name');
        $this->addColumn(new \Zend_Db_Expr(
                "CONCAT(COALESCE(grs_last_name, '-'), ', ', COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, ''))"
                ), 'respondent_name');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems\Model\LogModel
     */
    public function applyBrowseSettings($detailed = false)
    {
        $this->resetOrder();

        //Not only active, we want to be able to read the log for inactive organizations too
        $orgs = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations');

        $this->set('gla_created', 'label', $this->_('Date'));
        $this->set('gls_name', 'label', $this->_('Action'));
        $this->set('gla_organization', 'label', $this->_('Organization'), 'multiOptions', $orgs);
        $this->set('staff_name', 'label', $this->_('Staff'));
        $this->set('gla_role', 'label', $this->_('Role'));
        $this->set('respondent_name', 'label', $this->_('Respondent'));

        $jdType = new JsonData();
        $this->set('gla_message', 'label', $this->_('Message'));
        $jdType->apply($this, 'gla_message', $detailed);

        if ($detailed) {
            $this->set('gla_data', 'label', $this->_('Data'));
            $jdType->apply($this, 'gla_data', $detailed);

            $this->set('gla_method', 'label', $this->_('Method'));
            $this->set('gla_remote_ip', 'label', $this->_('IP address'));
        }
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\LogModel
     */
    public function applyDetailSettings()
    {
        $this->applyBrowseSettings(true);
    }
}
