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
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class that extends Array object to add Gems specific functions.
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Project_ProjectSettings extends ArrayObject
{
    /**
     * The minimum length for the password of a super admin
     * on a production server.
     *
     * @var int
     */
    protected $minimumSuperPasswordLength = 10;

    /**
     * Array of required keys. Give a string value for root keys
     * or name => array() values for required subs keys.
     *
     * Deeper levels are not supported at the moment.
     *
     * @see checkRequiredValues()
     *
     * @var array
     */
    protected $requiredKeys = array(
        'css' => array('gems'),
        'locale' => array('default'),
        'salt',
        );

    /**
     * Creates the object and checks for required values.
     *
     * @param mixed $array
     */
    public function __construct($array)
    {
        // Convert to array when needed
        if ($array instanceof Zend_Config) {
            $array = $array->toArray();
        } elseif ($array instanceof ArrayObject) {
            $array = $array->getArrayCopy();
        } elseif (! is_array($array)) {
            $array = (array) $array;
        }

        parent::__construct($array, ArrayObject::ARRAY_AS_PROPS);

        $this->checkRequiredValues();
    }

    /**
     * This function checks for the required project settings.
     *
     * Overrule this function or the $requiredParameters to add extra required settings.
     *
     * @see $requiredParameters
     *
     * @return void
     */
    protected function checkRequiredValues()
    {
        $missing = array();
        foreach ($this->requiredKeys as $key => $names) {
            if (is_array($names)) {
                if (! ($this->offsetExists($key) && $this->offsetGet($key))) {
                    $subarray = array();
                } else {
                    $subarray = $this->offsetGet($key);
                }
                foreach ($names as $name) {
                    if (! isset($subarray[$name])) {
                        $missing[] = $key . '.' . $name;
                    }
                }
            } else {
                if (! ($this->offsetExists($names) && $this->offsetGet($names))) {
                    $missing[] = $names;
                }
            }
        }

        if ($missing) {
            if (count($missing) == 1) {
                $error = sprintf("Missing required project setting: '%s'.", reset($missing));
            } else {
                $error = sprintf("Missing required project settings: '%s'.", implode("', '", $missing));
            }
            throw new Gems_Exception_Coding($error);
        }

        $superPassword = $this->getSuperAdminPassword();
        if ((APPLICATION_ENV === 'production') && $this->getSuperAdminName() && $superPassword) {
            if (strlen($superPassword) < $this->minimumSuperPasswordLength) {
                $error = sprintf("Project setting 'admin.pwd' is shorter than %d characters. That is not allowed.", $this->minimumSuperPasswordLength);
                throw new Gems_Exception_Coding($error);
            }
        }

        if (! ($this->offsetExists('name') && $this->offsetGet('name'))) {
            $this->offsetSet('name', GEMS_PROJECT_NAME);
        }

        $this->offsetSet('multiLocale', isset($project->locales) && (count($project->locales) > 1));
    }

    /**
     * Checks the super admin password, if it exists
     *
     * @param string $password
     * @return boolean True if the password is correct.
     */
    public function checkSuperAdminPassword($password)
    {
        return $password && ($password == $this->getSuperAdminPassword($password));
    }

    /**
     * Returns the factor used to delay account reloading.
     *
     * @return int
     */
    public function getAccountDelayFactor()
    {
        if (isset($this->account['delayFactor'])) {
            return intval($this->account['delayFactor']);
        } else {
            return 4;
        }
    }

    /**
     * Returns the public name of this project.
     * @return string
     */
    public function getName()
    {
        return $this->offsetGet('name');
    }

    /**
     * Returns the super admin name, if any
     *
     * @return string
     */
    public function getSuperAdminName()
    {
        if (isset($this->admin['user'])) {
            return $this->admin['user'];
        }
    }

    /**
     * Returns the super admin password, if it exists
     *
     * @return string
     */
    protected function getSuperAdminPassword()
    {
        if (isset($this->admin['pwd'])) {
            return $this->admin['pwd'];
        }
    }

    /**
     * Returns a salted hash on the
     *
     * @param string $value The value to hash
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function getValueHash($value)
    {
        $salt = $this->offsetExists('salt') ? $this->offsetGet('salt') : '';

        if (false === strpos($salt, '%s')) {
            $salted = $salt . $value;
        } else {
            $salted = sprintf($salt, $value);
        }

        // MUtil_Echo::track($value, md5($salted));

        return md5($salted, false);
    }

    /**
     * Returns a salted hash on the
     *
     * @param string $name Fieldname
     * @param string $value The value to hash
     * @param string $isNew True when new
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function getValueHashForModel($name, $value, $isNew = false)
    {
        return $this->getValueHash($value);
    }
}
