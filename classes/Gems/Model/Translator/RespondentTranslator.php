<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Translator;

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class RespondentTranslator extends \Gems\Model\Translator\StraightTranslator
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The task used for import
     *
     * @var string
     */
    protected $saveTask = 'Import\\SaveRespondentTask';

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->db instanceof \Zend_Db_Adapter_Abstract) && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil\Model\ModelException
     */
    public function getFieldsTranslations()
    {
        $fieldList = parent::getFieldsTranslations();

        // Add the key values (so organization id is present)
        $keys = array_combine(array_values($this->_targetModel->getKeys()), array_values($this->_targetModel->getKeys()));
        $fieldList = $fieldList + $keys;

        $fieldList['grs_email'] = 'gr2o_email';

        return $fieldList;
    }

    /**
     * Prepare for the import.
     *
     * @return \MUtil\Model\ModelTranslatorAbstract (continuation pattern)
     */
    public function startImport()
    {
        if ($this->_targetModel instanceof \MUtil\Model\ModelAbstract) {
            $this->_targetModel->set('grs_gender', 'extraValueKeys', array('V' => 'F'));
        }

        return parent::startImport();
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }

        if ((! isset($row['grs_id_user'])) && isset($row['gr2o_patient_nr'], $row['gr2o_id_organization'])) {
            $sql = 'SELECT gr2o_id_user
                    FROM gems__respondent2org
                    WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?';

            $id = $this->db->fetchOne($sql, array($row['gr2o_patient_nr'], $row['gr2o_id_organization']));

            if ($id) {
                $row['grs_id_user']  = $id;
                $row['gr2o_id_user'] = $id;
            }
        }

        if (empty($row['gr2o_email'])) {
            $row['calc_email'] = 1;
        }
        // \MUtil\EchoOut\EchoOut::track($row);

        return $row;
    }
}
