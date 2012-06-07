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
class Gems_Upgrades extends Gems_UpgradesAbstract
{
    public function __construct()
    {
        //Important, ALWAYS run the contruct of our parent object
        parent::__construct();

        //Now set the context
        $this->setContext('gems');
        //And add our patches
        $this->register(array($this, 'Upgrade143to150'), 'Upgrade from 1.4.3 to 1.5.0');
        $this->register(array($this, 'Upgrade150to151'), 'Upgrade from 1.5.0 to 1.5.1');
        $this->register(array($this, 'Upgrade151to152'), 'Upgrade from 1.5.1 to 1.5.2');
        $this->register(array($this, 'Upgrade152to153'), 'Upgrade from 1.5.2 to 1.5.3');
        $this->register(array($this, 'Upgrade153to154'), 'Upgrade from 1.5.3 to 1.5.4');
    }


    /**
     * To upgrade from 143 to 15 we need to do some work:
     * 1. execute db patches 42 and 43
     * 2. create new tables
     */
    public function Upgrade143to150()
    {
        $this->_batch->addTask('Db_ExecutePatch', 42);
        $this->_batch->addTask('Db_ExecutePatch', 43);

        $this->_batch->addTask('Db_CreateNewTables');

        $this->_batch->addTask('Echo', $this->_('Syncing surveys for all sources'));

        //Now sync the db sources to allow limesurvey source to add a field to the tokentable
        $model = new MUtil_Model_TableModel('gems__sources');
        $data  = $model->load(false);

        foreach ($data as $row) {
            $this->_batch->addTask('Tracker_SourceSyncSurveys', $row['gso_id_source']);
        }

        return true;
    }

    /**
     * To upgrade to 1.5.1 just execute patchlevel 44
     */
    public function Upgrade150to151()
    {
        $this->_batch->addTask('Db_ExecutePatch', 44);

        return true;
    }

    /**
     * To upgrade to 1.5.2 just execute patchlevel 45
     */
    public function Upgrade151to152()
    {
        $this->_batch->addTask('Db_ExecutePatch', 45);

        return true;
    }

    /**
     * To upgrade to 1.5.2 just execute patchlevel 46
     */
    public function Upgrade152to153()
    {
        $this->_batch->addTask('Db_ExecutePatch', 46);

        return true;
    }

    /**
     * To upgrade to 1.5.4 just execute patchlevel 47
     */
    public function Upgrade153to154()
    {
        $this->_batch->addTask('Db_ExecutePatch', 47);

        return true;
    }
}