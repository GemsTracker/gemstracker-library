<?php

/**
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

        $this->setKeys(array(\Gems_Model::LOG_ITEM_ID => 'gla_id'));
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->addColumn(new \Zend_Db_Expr(sprintf(
                "CASE WHEN gla_by IS NULL THEN '%s'
                    ELSE CONCAT(
                        COALESCE(gsf_last_name, '-'),
                        ', ',
                        COALESCE(CONCAT(gsf_first_name, ' '), ''),
                        COALESCE(gsf_surname_prefix, '')
                        )
                    END",
                $this->_('(no user)')
                )), 'staff_name');
        $this->addColumn(new \Zend_Db_Expr(sprintf(
                "CASE WHEN grs_id_user IS NULL THEN '%s'
                    ELSE CONCAT(
                        COALESCE(grs_last_name, '-'),
                        ', ',
                        COALESCE(CONCAT(grs_first_name, ' '), ''),
                        COALESCE(grs_surname_prefix, '')
                        )
                    END",
                $this->_('(no respondent)')
                )), 'respondent_name');
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
