<?php

/**
 * @package    Gems
 * @subpackage Communication
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Communication;

/**
 * A simple value-object containing respondent information
 *
 * @package    Gems
 * @subpackage Communication
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class RespondentContainer
{
    private $_patientId     = null;
    private $_firstName     = null;
    private $_lastName      = null;
    private $_surnamePrefix = null;
    private $_bsn           = null;
    private $_gender        = null;
    private $_birthday      = null;

    public function getPatientId()
    {
        return $this->_patientId;
    }

    public function setPatientId($patientId)
    {
        $this->_patientId = $patientId;
    }

    public function getFirstName()
    {
        return $this->_firstName;
    }

    public function setFirstName($firstName)
    {
        $this->_firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->_lastName;
    }

    public function setLastName($lastName)
    {
        $this->_lastName = $lastName;
    }

    public function getSurnamePrefix()
    {
        return $this->_surnamePrefix;
    }

    public function setSurnamePrefix($surnamePrefix)
    {
        $this->_surnamePrefix = $surnamePrefix;
    }

    public function getBsn()
    {
        return (string) $this->_bsn;
    }

    public function setBsn($bsn)
    {
        $filter = new \MUtil\Filter\Dutch\Burgerservicenummer();
        $this->_bsn = $filter->filter($bsn);
    }

    public function getGender()
    {
        return $this->_gender;
    }

    public function setGender($gender)
    {
        $this->_gender = $gender;
    }

    public function getBirthday()
    {
        return $this->_birthday;
    }

    public function setBirthday($birthday)
    {
        $this->_birthday = $birthday;
    }
}