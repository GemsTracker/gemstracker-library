<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Tracker
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Respondent.php 967 2012-10-02 07:47:36Z mennodekker $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Tracker_Respondent extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var array The gems respondent and respondent to org data
     */
    protected $_gemsData;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Boolean true if Respondent exists in the database
     */
    public $exists = false;

	/**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var int The highest grs_phone_nr phone number used in this project
     */
    protected $maxPhoneNumber = 4;

    /**
     *
     * @var Gems_Model_RespondentModel
     */
	protected $model;

    /**
     *
     * @var integer Organization Id
     */
    private $organizationId;

    /**
     *
     * @var string Patient Id
     */
    private $patientId;

    /**
     * @var int respondentId
     */
    protected $respondentId;

    /**
     *
     * @var string Respondent language
     */
    protected $respondentLanguage;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $patientId   Patient number, you can use $respondentId instead
     * @param int $organizationId Organization id
     * @param int $respondentId   Optional respondent id, used when patient id is empty
     */
	public function __construct($patientId, $organizationId, $respondentId = null)
    {
        $this->patientId      = $patientId;
        $this->organizationId = $organizationId;
        $this->respondentId   = $respondentId;
	}

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->model = $this->loader->getModels()->getRespondentModel(true);

        // Load the data
        $this->refresh();
    }

    /**
     * Get a (single char code for the gender (normally M/F/U))text
     *
     * @return MUtil_Date or null
     */
    public function getBirthday()
    {
        return $this->_gemsData['grs_birthday'];
    }

    /**
     * Get Email adres of respondent
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->_gemsData['grs_email'];
    }

    /**
     * Get First name of respondent
     * @return string
     */
    public function getFirstName()
    {
        return $this->_gemsData['grs_first_name'];
    }

    /**
     * Get the formal name of respondent
     * @return string
     */
    public function getFullName()
    {

        $genderGreetings = $this->util->getTranslated()->getGenderHello($this->getLanguage());

        $greeting = $genderGreetings[$this->getGender()];

        return $greeting . ' ' . $this->getName();
    }

    /**
     * Get a single char code for the gender (normally M/F/U)
     * @return string
     */
    public function getGender()
    {
        return $this->_gemsData['grs_gender'];
    }

    /**
     * Get the propper greeting of respondent
     * @return string
     */
    public function getGreeting()
    {

        $genderGreetings = $this->util->getTranslated()->getGenderGreeting($this->getLanguage());

        $greeting = $genderGreetings[$this->getGender()];

        return $greeting . ' ' . $this->getLastName();
    }

    /**
     * Get the propper greeting of respondent
     * @return string
     */
    public function getGreetingNL()
    {
        $genderGreetings = $this->util->getTranslated()->getGenderGreeting($this->getLanguage());

        $greeting = $genderGreetings[$this->_gemsData['grs_gender']];

        return $greeting . ' ' . ucfirst($this->getLastName());
    }

    /**
     *
     * @return int The respondent id
     */
    public function getId()
    {
        return $this->respondentId;
    }

    /**
     * Get the respondents prefered language
     * @return string
     */
    public function getLanguage() {
        if (!isset($this->respondentLanguage)) {
            $this->respondentLanguage = $this->_gemsData['grs_iso_lang'];
        }
        return $this->respondentLanguage;
    }

    /**
     * Get Last name of respondent
     * @return string
     */
    public function getLastName()
    {
        $lastname = '';
        if (!empty($this->_gemsData['grs_surname_prefix'])) {
            $lastname .= $this->_gemsData['grs_surname_prefix'] . ' ';
        }
        $lastname .= $this->_gemsData['grs_last_name'];
        return $lastname;
    }

    /**
     * Get the full name (firstname, prefix and last name)
     * @return string
     */
    public function getName()
    {
        $fullName = $this->getFirstName() . ' ' . $this->getLastName();

        return $fullName;
    }

    /**
     *
     * @return Gems_User_Organization
     */
    public function getOrganization()
    {
        return $this->loader->getOrganization($this->organizationId);
    }

    /**
     *
     * @return integer Organization ID
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    /**
     * Get Patient number of respondent
     *
     * @deprecated since version 1.6.4
     * @return string
     */
    public function getPatientId()
    {
        return $this->patientId;
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        return $this->patientId;
    }

    /**
     * Get the first entered phonenumber of the respondent.
     *
     * @return string
     */
    public function getPhonenumber()
    {
        for ($i = 1; $i <= $this->maxPhoneNumber; $i++) {
            if (isset($this->_gemsData['grs_phone_' . $i]) && ! empty($this->_gemsData['grs_phone_' . $i])) {
                return $this->_gemsData['grs_phone_' . $i];
            }
        }

        return null;
    }

    /**
     * Refresh the data
     */
	public function refresh()
    {
        $default = true;
        $filter  = array();

        if ($this->patientId) {
            $filter['gr2o_patient_nr'] = $this->patientId;
            $default = false;
        } elseif ($this->respondentId) {
            $filter['gr2o_id_user'] = $this->respondentId;
            $default = false;
        }
        if ($this->organizationId) {
            $filter['gr2o_id_organization'] = $this->organizationId;
        }

        $this->model->setFilter($filter);

        $this->_gemsData = $this->model->loadFirst();

        if ($this->_gemsData) {
            $this->exists = true;

            $this->patientId      = $this->_gemsData['gr2o_patient_nr'];
            $this->organizationId = $this->_gemsData['gr2o_id_organization'];
            $this->respondentId   = $this->_gemsData['gr2o_id_user'];
        } else {
            $this->_gemsData = $this->model->loadNew();
            $this->exists = false;
        }
	}

    /**
     * Overwrite the respondents prefered language
     */
    public function setLocale($locale) {
        $this->respondentLanguage = $locale;
    }
}