<?php

/**
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class RespondentRelationInstance extends \Gems\Registry\TargetAbstract 
{
    /**
     * The model this instance is designed for
     *
     * @var \Gems\Model\RespondentRelationModel
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
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    public function __construct($model, $data) 
    {
        // Sanity check:
        if (!($model instanceof \Gems\Model\RespondentRelationModel)) {
            throw new \Gems\Exception\Coding('Please provide the correct type of model');
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
            $this->_defaults = [
                'grr_first_name' => '',
                'grr_last_name'  => '',
                'grr_email'      => '',
                'grr_phone'      => '',
                'grr_mailable'   => 0
            ];
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
     * @param \MUtil\Date|null $date
     * @return int
     */
    public function getAge($date = NULL)
    {
        if (is_null($date)) {
            $date = new \MUtil\Date();
        }

        if ($date instanceof \MUtil\Date) {
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

    /**
     * Get the proper Dear mr./mrs/ greeting of respondent
     * 
     * @return string
     */
    public function getDearGreeting($language)
    {
        $genderDears = $this->util->getTranslated()->getGenderDear($language);

        $gender = $this->getGender();
        if (isset($genderDears[$gender])) {
            $greeting = $genderDears[$gender] . ' ';
        } else {
            $greeting = '';
        }

        return $greeting . $this->getLastName();
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
        $genderGreetings = $this->util->getTranslated()->getGenderGreeting($language);
        $greeting = $genderGreetings[$this->getGender()] . ' ' . ucfirst($this->getLastName());

        return $greeting;
    }

    public function getHello($language)
    {
        $genderHello = $this->util->getTranslated()->getGenderHello($language);
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

    public function getPhoneNumber()
    {
        return $this->_data['grr_phone'];
    }

    /**
     * Can mails be sent for this relation?
     *
     * This only check the mailable attribute, not the presence of a mailaddress
     *
     * @return boolean
     */
    public function isMailable()
    {
        if (!array_key_exists('grr_mailable', $this->_data)) {
            $this->refresh();
        }

        return $this->_data['grr_mailable'] == 1;
    }



}
