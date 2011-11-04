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
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Loader extends Gems_Loader_LoaderAbstract
{
    /**
     *
     * @var Gems_Events
     */
    protected $events;

    /**
     *
     * @var Gems_Export
     */
    protected $export;

    /**
     *
     * @var Gems_Model
     */
    protected $models;

    /**
     *
     * @var Gems_Pdf
     */
    protected $pdf;

    /**
     *
     * @var Gems_Roles
     */
    protected $roles;

    /**
     *
     * @var Gems_Selector
     */
    protected $selector;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Gems_Upgrades
     */
    protected $upgrades;

    /**
     *
     * @var Gems_User_UserLoader
     */
    protected $userLoader;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @var Gems_Versions
     */
    protected $versions;

    /**
     * Load project specific menu or general Gems menu otherwise
     *
     * @param GemsEscort $escort
     * @return Gems_Menu
     */
    public function createMenu(GemsEscort $escort)
    {
        return $this->_loadClass('Menu', true, func_get_args());
    }

    /**
     *
     * @return gems_Events
     */
    public function getEvents()
    {
        return $this->_getClass('events');
    }

    /**
     *
     * @return Gems_Export
     */
    public function getExport()
    {
        return $this->_getClass('export');
    }

    /**
     *
     * @return Gems_Model
     */
    public function getModels()
    {
        return $this->_getClass('models', 'model');
    }

    /**
     *
     * @return Gems_Pdf
     */
    public function getPdf()
    {
        return $this->_getClass('pdf');
    }

    /**
     *
     * @param GemsEscort $escort
     * @return Gems_Roles
     */
    public function getRoles(GemsEscort $escort)
    {
        return $this->_getClass('roles', null, array($escort));
    }

    /**
     *
     * @return Gems_Selector
     */
    public function getSelector()
    {
        return $this->_getClass('selector');
    }

    /**
     *
     * @return Gems_Tracker_TrackerInterface
     */
    public function getTracker()
    {
        return $this->_getClass('tracker');
    }

    /**
     *
     * @return Gems_Upgrades
     */
    public function getUpgrades()
    {
        return $this->_getClass('upgrades');
    }

    /**
     *
     * @param string $login_name
     * @param int $organization Only used when more than one organization uses this $login_name
     * @return Gems_User_UserAbstract
     */
    public function getUser($login_name, $organization)
    {
        $loader = $this->getUserLoader();

        return $loader->getUser($login_name, $organization);
    }

    /**
     *
     * @return Gems_User_UserLoader
     */
    protected function getUserLoader()
    {
        return $this->_getClass('userLoader', 'User_UserLoader');
    }

    /**
     *
     * @return Gems_Util
     */
    public function getUtil()
    {
        return $this->_getClass('util');
    }

    /**
     *
     * @return Gems_Versions
     */
    public function getVersions()
    {
        return $this->_getClass('versions');
    }
}