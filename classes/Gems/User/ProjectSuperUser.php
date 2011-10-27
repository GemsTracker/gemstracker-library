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
 *
 *
 * @package    Gems
 * @subpackage User
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_User_ProjectSuperUser extends Gems_User_UserAbstract
{
    const MIN_PASSWORD_LENGTH = 10;

    /**
     *
     * @var ArrayObject
     */
    protected $project;

    /**
     *
     * @var Gems_Util_Translated
     */
    protected $translated;

    /**
     * Check that the password is correct for this user.
     *
     * @param string $password Unencrypted password
     * @return boolean
     */
    public function checkPassword($password)
    {
        if (isset($this->project->admin['pwd']) && ($this->project->admin['pwd'] == $password)) {
            if (APPLICATION_ENV === 'production') {
                if (strlen($string) < self::MIN_PASSWORD_LENGTH) {
                    throw new Gems_Exception(sprintf($this->translated->_('The password for the super user should be at least %s characters long on production systems.'), self::MIN_PASSWORD_LENGTH));
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Returns true if the role of this user has the given privilege.
     *
     * @param string $privilege
     * @return bool
     */
    public function hasPrivilege($privilege)
    {
        // Overloaded!!
        //
        // Return true also for any not 'nologin' privilege
        return parent::hasPrivilege($privilege) || (! $this->acl) || (! $this->acl->isAllowed('nologin', null, $privilege));
    }

    /**
     * Intialize the values for this user.
     *
     * Skipped when the user is the active user and is stored in the session.
     *
     * @param string $login_name
     * @param int $organization Only used when more than one organization uses this $login_name
     * @return boolean False when the object could not load.
     */
    protected function initVariables($login_name, $organization)
    {
        $this->setRole('super'); // sublime superior master diety ?
        return true;
    }

}
