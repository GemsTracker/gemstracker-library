<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 24-apr-2014 14:46:04
 */
class Gems_Model_Translator_RespondentAnswerTranslator extends \Gems_Model_Translator_AnswerTranslatorAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * If the token can be created find the respondent track for the token
     *
     * @param array $row
     * @return int|null
     */
    protected function findRespondentTrackFor(array $row)
    {
        if (! (isset($row[$this->patientNrField], $row[$this->orgIdField]) &&
                $row[$this->patientNrField] &&
                $row[$this->orgIdField])) {
            return null;
        }

        $trackId = $this->getTrackId();
        if (! $trackId) {
            return null;
        }

        $select = $this->db->select();
        $select->from('gems__respondent2track', array('gr2t_id_respondent_track'))
                ->joinInner(
                        'gems__respondent2org',
                        'gr2t_id_user = gr2o_id_user AND gr2t_id_organization = gr2o_id_organization',
                        array()
                        )
                ->where('gr2o_patient_nr = ?', $row[$this->patientNrField])
                ->where('gr2o_id_organization = ?', $row[$this->orgIdField])
                ->where('gr2t_id_track = ?');


        $select->order(new \Zend_Db_Expr("CASE
            WHEN gr2t_start_date IS NOT NULL AND gr2t_end_date IS NULL THEN 1
            WHEN gr2t_start_date IS NOT NULL AND gr2t_end_date IS NOT NULL THEN 2
            ELSE 3 END ASC"))
                ->order('gr2t_start_date DESC')
                ->order('gr2t_end_date DESC')
                ->order('gr2t_created');

        $respTrack = $this->db->fetchOne($select);

        if ($respTrack) {
            return $respTrack;
        }

        return null;
    }

    /**
     * Find the token id using the passed row data and
     * the other translator parameters.
     *
     * @param array $row
     * @return string|null
     */
    protected function findTokenFor(array $row)
    {
        if (! (isset($row[$this->patientNrField], $row[$this->orgIdField]) &&
                $row[$this->patientNrField] &&
                $row[$this->orgIdField] &&
                $this->getSurveyId())) {
            return null;
        }

        $select = $this->db->select();
        $select->from('gems__tokens', array('gto_id_token'))
                ->joinInner(
                        'gems__respondent2org',
                        'gto_id_respondent = gr2o_id_user AND gto_id_organization = gr2o_id_organization',
                        array()
                        )
                ->where('gr2o_patient_nr = ?', $row[$this->patientNrField])
                ->where('gr2o_id_organization = ?', $row[$this->orgIdField])
                ->where('gto_id_survey = ?', $this->getSurveyId());

        $trackId = $this->getTrackId();
        if ($trackId) {
            $select->where('gto_id_track = ?', $trackId);
        }

        $select->order(new \Zend_Db_Expr("CASE
            WHEN gto_completion_time IS NULL AND gto_valid_from IS NOT NULL THEN 1
            WHEN gto_completion_time IS NULL AND gto_valid_from IS NULL THEN 2
            ELSE 3 END ASC"))
                ->order('gto_completion_time DESC')
                ->order('gto_valid_from ASC')
                ->order('gto_round_order');

        $token = $this->db->fetchOne($select);

        if ($token) {
            return $token;
        }

        return null;
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getRespondentAnswerTranslations()
    {
        $this->_targetModel->set($this->patientNrField, 'label', $this->_('Patient ID'),
                'order', 5,
                'required', true,
                'type', \MUtil_Model::TYPE_STRING
                );
        $this->_targetModel->set($this->orgIdField, 'label', $this->_('Organization ID'),
                'multiOptions', $this->util->getDbLookup()->getOrganizationsWithRespondents(),
                'order', 6,
                'required', true,
                'type', \MUtil_Model::TYPE_STRING
                );

        return array(
            $this->patientNrField => $this->patientNrField,
            $this->orgIdField     => $this->orgIdField,
            );
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        if (! $this->_targetModel instanceof \MUtil_Model_ModelAbstract) {
            throw new \MUtil_Model_ModelTranslateException(sprintf('Called %s without a set target model.', __FUNCTION__));
        }
        // \MUtil_Echo::track($this->_targetModel->getItemNames());

        return $this->getRespondentAnswerTranslations() + parent::getFieldsTranslations();
    }

    /**
     * Returns an array of the field names that are required
     *
     * @return array of fields sourceName => targetName
     */
    public function getRequiredFields()
    {
        return array(
            $this->patientNrField => $this->patientNrField,
            $this->orgIdField     => $this->orgIdField,
            );
    }

    /**
     * Get the error message for when no token exists
     *
     * @return string
     */
    public function getNoTokenError(array $row, $key)
    {
        $messages = array();
        if (! (isset($row[$this->patientNrField]) && $row[$this->patientNrField])) {
            $messages[] = sprintf($this->_('Missing respondent number in %s field.'), $this->patientNrField);
        }
        if (! (isset($row[$this->orgIdField]) && $row[$this->orgIdField])) {
            $messages[] = sprintf($this->_('Missing organization number in %s field.'), $this->orgIdField);
        }
        if (! $this->getSurveyId()) {
            $messages[] = $this->_('Missing survey definition.');
        }
        if ($messages) {
            return implode(' ', $messages);
        }

        if (! $this->_skipUnknownPatients) {
            $respondent   = $this->loader->getRespondent($row[$this->patientNrField], $row[$this->orgIdField]);
            $organization = $respondent->getOrganization();

            if (! $organization->exists()) {
                return sprintf(
                        $this->_('Organization %s (specified for respondent %s) does not exist.'),
                        $respondent->getOrganizationId(),
                        $respondent->getPatientNumber()
                        );
            }
            if (! $respondent->exists) {
                return sprintf(
                        $this->_('Respondent %s does not exist in organization %s.'),
                        $respondent->getPatientNumber(),
                        $organization->getName()
                        );
            }

            $tracker = $this->loader->getTracker();
            $trackId = $this->getTrackId();
            if ($trackId) {
                $trackEngine = $tracker->getTrackEngine($trackId);
                if (! $trackEngine->getTrackName()) {
                    return sprintf($this->_('Track id %d does not exist'), $trackId);
                }

                $select = $this->db->select();
                $select->from('gems__respondent2track')
                        ->joinInner(
                                'gems__reception_codes',
                                'gr2t_reception_code = grc_id_reception_code',
                                array()
                                )
                        ->where('gr2t_id_user = ?', $respondent->getId())
                        ->where('gr2t_id_organization = ?', $respondent->getOrganizationId())
                        ->where('grc_success = 1');

                if (! $this->db->fetchOne($select)) {
                    return sprintf(
                            $this->_('Respondent %s does not have a valid %s track.'),
                            $respondent->getPatientNumber(),
                            $trackEngine->getTrackName()
                            );
                }
            }

            $survey      = $tracker->getSurvey($this->getSurveyId());
            $tokenSelect = $tracker->getTokenSelect();
            $tokenSelect->andReceptionCodes()
                    ->forRespondent($respondent->getId(), $respondent->getOrganizationId())
                    ->forSurveyId($this->getSurveyId());

            if ($tokenSelect->fetchOne()) {
                $tokenSelect->onlySucces();
                if (! $tokenSelect->fetchOne()) {
                    return sprintf(
                            $this->_('Respondent %s has only deleted %s surveys.'),
                            $respondent->getPatientNumber(),
                            $survey->getName()
                            );
                }
            } else {
                return sprintf(
                        $this->_('Respondent %s has no %s surveys.'),
                        $respondent->getPatientNumber(),
                        $survey->getName()
                        );
            }
        }

        return parent::getNoTokenError($row, $key);
    }
}
