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
 * @package    Gems
 * @subpackage Communication
 */

/**
 * A simple value-object containing respondent information
 *
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @version    $Id$
 * @package    Gems
 * @subpackage Communication
 */
class Gems_Communication_RespondentContainer
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
        $filter = new MUtil_Filter_Dutch_Burgerservicenummer();
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