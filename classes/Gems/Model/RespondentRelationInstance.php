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
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentRelationInstance.php 2763 2015-10-30 18:33:48Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Model_RespondentRelationInstance extends \Gems_Registry_TargetAbstract {

    /**
     * The model this instance is designed for
     *
     * @var \Gems_Model_RespondentRelationModel
     */
    protected $_model;

    /**
     * Holds the data for the current instance
     *
     * @var array
     */
    protected $_data;

    /**
     * Default data, loaded by _getDefaults
     *
     * @var array
     */
    protected $_defaults = array();

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;


    public function __construct($model, $data) {
        // Sanity check:
        if (!($model instanceof EMC_Model_RespondentRelationModel)) {
            throw new \Gems_Exception_Coding('Please provide the correct type of model');
        }

        $this->_model = $model;
        $this->_data  = $data;
    }

    public function __toString() {
        return $this->_data;
    }

    protected function _getDefaults()
    {
        if (empty($this->_defaults)) {
        $this->_defaults = array(
            'grr_first_name' => '',
            'grr_last_name' => '',
            'grr_email' => ''
            );
        }

        return $this->_defaults;
    }

    public function afterRegistry() {
        parent::afterRegistry();

        // Make sure we have at least some default data
        if (empty($this->_data)) {
            $this->_data = $this->_getDefaults();
        }
    }

    /**
     * Returns current age or at a given date when supplied
     *
     * @param \MUtil_Date|null $date
     * @return int
     */
    public function getAge($date = NULL)
    {
        if (is_null($date)) {
            $date = new \MUtil_Date();
        }

        if ($date instanceof \MUtil_Date) {
            // Now calculate age
            $birthDate = $this->getBirthDate();
            if ($birthDate instanceof \Zend_Date) {
				$age = $date->get('Y') - $birthDate->get('Y');
				if ($date->get('MMdd') < $birthDate->get('MMdd')) {
					$age--;
				}
			} else {
				return;
			}
        }

        return $age;
    }

    public function getBirthDate()
    {
        return array_key_exists('grr_birthdate', $this->_data) ? $this->_data['grr_birthdate'] : null;
    }

    public function getEmail()
    {
        return $this->_data['grr_email'];
    }

    public function getFirstName()
    {
        return $this->_data['grr_first_name'];
    }

    /**
     * M / F / U
     *
     * @return string
     */
    public function getGender()
    {
        $gender = 'U';
        if (isset($this->_data['grr_gender'])) {
            $gender = $this->_data['grr_gender'];
        }

        return $gender;
    }

    public function getGreeting($language)
    {
        $genderGreetings = $this->loader->getUtil()->getTranslated()->getGenderGreeting($language);
        $greeting = $genderGreetings[$this->getGender()] . ' ' . ucfirst($this->getLastName());

        return $greeting;
    }

    public function getHello($language)
    {
        $genderHello = $this->loader->getUtil()->getTranslated()->getGenderHello($language);
        $hello = $genderHello[$this->getGender()] . ' ' . ucfirst($this->getLastName());

        return $hello;
    }

    public function getLastName()
    {
        return $this->_data['grr_last_name'];
    }

    public function getRelationId()
    {
        return array_key_exists('grr_id', $this->_data) ? $this->_data['grr_id'] : null;
    }

    /**
     * Return string with first and lastname, separated with a space
     *
     * @return string
     */
    public function getName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }



}