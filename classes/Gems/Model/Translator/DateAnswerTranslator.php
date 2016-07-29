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
class Gems_Model_Translator_DateAnswerTranslator extends \Gems_Model_Translator_RespondentAnswerTranslator
{
    /**
     * The name of the field to (temporarily) store the patient nr in
     *
     * @var string
     */
    protected $completionField = 'completion_date';

    /**
     * Find the token id using the passed row data and
     * the other translator parameters.
     *
     * @param array $row
     * @return string|null
     */
    protected function findTokenFor(array $row)
    {
        if (isset($row[$this->patientNrField], $row[$this->orgIdField], $row[$this->completionField]) &&
                $row[$this->patientNrField] &&
                $row[$this->orgIdField] &&
                $row[$this->completionField]) {

            if ($row[$this->completionField] instanceof \Zend_Date) {
                $compl = $row[$this->completionField]->toString(\Gems_Tracker::DB_DATETIME_FORMAT);
            } else {
                $compl = $row[$this->completionField];
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
                    ->where('gto_id_survey = ?', $this->getSurveyId())
                    ->where('gto_valid_from <= ?', $compl)
                    ->where('(gto_valid_until >= ? OR gto_valid_until IS NULL)', $compl);

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
        }

        return null;
    }

    /**
     * Get the error message for when no token exists
     *
     * @return string
     */
    public function getNoTokenError(array $row, $key)
    {
        if (! (isset($row[$this->completionField]) && $row[$this->completionField])) {
            return $this->_('Missing date in completion_date field.');
        }
        if (isset($row[$this->patientNrField], $row[$this->orgIdField]) &&
                $row[$this->patientNrField] &&
                $row[$this->orgIdField]) {

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

            if ($this->db->fetchOne($select)) {
                $survey = $this->loader->getTracker()->getSurvey($this->getSurveyId());

                return sprintf(
                        $this->_('Respondent %s has no valid token for the %s survey.'),
                        $row[$this->patientNrField],
                        $survey->getName()
                        );
            }
        }

        return parent::getNoTokenError($row, $key);
    }

    /**
     * Returns an array of the field names that are required
     *
     * @return array of fields sourceName => targetName
     */
    public function getRequiredFields()
    {
        return parent::getRequiredFields() + array(
            $this->completionField => $this->completionField,
            );
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getRespondentAnswerTranslations()
    {
        $this->_targetModel->set($this->completionField, 'label', $this->_('Patient ID'),
                'order', 8,
                'required', true,
                'type', \MUtil_Model::TYPE_DATETIME
                );
        return parent::getRespondentAnswerTranslations();
    }
}
