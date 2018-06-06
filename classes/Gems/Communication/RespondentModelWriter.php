<?php

/**
 * @package    Gems
 * @subpackage Communication
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Writer implementation to save respondents to the database
 *
 * @package    Gems
 * @subpackage Communication
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Communication_RespondentModelWriter implements \Gems_Communication_RespondentWriter
{
    /**
     * @var \Gems_Model_RespondentModel
     */
    private $_model = null;

    public function __construct()
    {
        $this->_model = \GemsEscort::getInstance()->getLoader()->getModels()->createRespondentModel();
    }

    /**
     * - Fetches respondent based on bsn / reception code and patient nr
     * - Creates the respondent if it does not exist, updates otherwise
     *
	 * @see \Gems_Model_RespondentModel
     * @see \Gems_Communication_RespondentWriter::writeRespondent()
	 *
	 * @param  \Gems_Communication_RespondentContainer $respondent
	 * @param  int $userId
	 * @return boolean True if a new respondent was added, false if one was updated
     */
    public function writeRespondent(\Gems_Communication_RespondentContainer $respondent, &$userId)
    {
        $parameters = $this->_model->applyParameters(
            array(
                'grs_ssn' => $respondent->getBsn(),
                'gr2o_reception_code' => \GemsEscort::RECEPTION_OK,
                'gr2o_patient_nr' => $respondent->getPatientId()
            )
        );

        $data = $this->_model->loadFirst();
        $isNew = false;

        if (empty($data)) {
            $isNew = true;
            $data = $this->_model->loadNew();
        }

        unset($data['gr2o_email']);

        $data['gr2o_patient_nr'] = $respondent->getPatientId();
        $data['grs_first_name'] = $respondent->getFirstName();
        $data['grs_last_name'] = $respondent->getLastName();
        $data['grs_surname_prefix'] = $respondent->getSurnamePrefix();
        $data['grs_ssn'] = $respondent->getBsn();
        $data['grs_gender'] = $respondent->getGender();
        $data['grs_birthday'] = $respondent->getBirthday();

        $data = $this->_model->save($data);

        $userId = $data['grs_id_user'];

        return $isNew;
    }
}