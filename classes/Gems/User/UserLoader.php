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
 * @subpackage User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * Loads users.
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_User_UserLoader extends Gems_Loader_TargetLoaderAbstract
{
    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'User';

    /**
     *
     * @var ArrayObject
     */
    protected $project;


    /**
     *
     */
    public function getCurrentUser()
    {
        static $currentUser;

        if (! $currentUser) {
            $currentUser = Gems_User_UserAbstract::getCurrentUser();

            if (! $currentUser) {
                $currentUser = $this->_loadClass('NoLoginUser', true, array(null, null));

                $currentUser->setAsCurrentUser();
            }
        }

        return $currentUser;
    }

    /**
     *
     * @param string $login_name
     * @param int $organization Only used when more than one organization uses this $login_name
     * @return Gems_User_UserAbstract
     */
    public function getUser($login_name, $organization)
    {
        if ($this->isProjectUser($login_name)) {
            return $this->loadProjectUser($login_name, $organization);
        }
    }

    protected function isProjectUser($login_name)
    {
        return isset($this->project->admin['user']) && ($this->project->admin['user'] == $login_name);
    }

    protected function loadProjectUser($login_name, $organization)
    {
        return $this->_getClass('ProjectSuperUser', null, array($login_name, $organization));
    }
}
