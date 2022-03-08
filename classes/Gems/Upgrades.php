<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Short description for Upgrades
 *
 * Long description for class Upgrades (if any)...
 *
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Upgrades extends \Gems_UpgradesAbstract
{
    public function __construct()
    {
        //Important, ALWAYS run the contruct of our parent object
        parent::__construct();

        //Now set the context
        $this->setContext('gems', 13);
        
        //And add our patches
        $this->register('Upgrade164to170', 'Upgrade from 1.6.4 to 1.7.0');
        $this->register('Upgrade170to171', 'Upgrade from 1.7.0 to 1.7.1');
        $this->register('Upgrade171to172', 'Upgrade from 1.7.1 to 1.7.2');
        $this->register('Upgrade172to181', 'Upgrade from 1.7.2 to 1.8.1');
        $this->register('Upgrade181to182', 'Upgrade from 1.8.1 to 1.8.2');
        $this->register('Upgrade182to183', 'Upgrade from 1.8.2 to 1.8.3');
        $this->register('Upgrade183to184', 'Upgrade from 1.8.3 to 1.8.4');
        $this->register('Upgrade184to185', 'Upgrade from 1.8.4 to 1.8.5');
        $this->register('Upgrade185to186', 'Upgrade from 1.8.5 to 1.8.6');
        $this->register('Upgrade186to187', 'Upgrade from 1.8.6 to 1.8.7');
        $this->register('Upgrade187to190', 'Upgrade from 1.8.7 to 1.9.0');
        $this->register('Upgrade190to191', 'Upgrade from 1.9.0 to 1.9.1');
        $this->register('Upgrade191to192', 'Upgrade from 1.9.1 to 1.9.2');
        /**
         * To have the new_project updated to the highest level, update
         *
         * /new_project/var/settings/upgrades.ini
         *
         * to have the right index for this context, normally when no index set on register
         * it will start counting at 1.
         */
    }

    /**
     * To upgrade to 1.7.0
     */
    public function Upgrade164to170()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 56);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.7.1
     */
    public function Upgrade170to171()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 57);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.7.2
     */
    public function Upgrade171to172()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 58);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.1
     */
    public function Upgrade172to181()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 59);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.2
     */
    public function Upgrade181to182()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 60);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Updates\\FillTokenReplacementsTask');

        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.3
     */
    public function Upgrade182to183()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 61);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.4
     */
    public function Upgrade183to184()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 62);
        $this->_batch->addTask('Updates_CompileTemplates');

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.5
     */
    public function Upgrade184to185()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 63);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.6
     */
    public function Upgrade185to186()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 64);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Updates_EncryptPasswords', 'gems__sources', 'gso_id_source', 'gso_ls_password');
        $this->_batch->addTask('AddTask', 'Updates_EncryptPasswords', 'gems__mail_servers', 'gms_from', 'gms_password');
        $this->_batch->addTask('AddTask', 'Updates_EncryptPasswords', 'gems__radius_config', 'grcfg_id', 'grcfg_secret');

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.7
     */
    public function Upgrade186to187()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 65);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.8.8
     */
    public function Upgrade187to190()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 66);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }
    
    /**
     * To upgrade to 1.9.1
     */
    public function Upgrade190to191()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 67);

        $this->_batch->addTask('Sites\\SiteUpgradeFromOrgAndProject');
        
        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.9.2
     */
    public function Upgrade191to192()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 68);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('AddTask', 'Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }
}
