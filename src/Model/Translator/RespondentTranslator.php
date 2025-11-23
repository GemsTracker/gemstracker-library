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

use Zalt\Model\Data\DataWriterInterface;

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
     * The task used for import
     *
     * @var string
     */
    protected $saveTask = 'Import\\SaveRespondentTask';

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil\Model\ModelException
     */
    public function getFieldsTranslations(): array
    {
        $fieldList = parent::getFieldsTranslations();

        $metaModel = $this->targetModel->getMetaModel();

        // Add the key values (so organization id is present)
        $keys = array_values($metaModel->getKeys());
        $fieldList = $fieldList + array_combine($keys, $keys);
        $fieldList['grs_email'] = 'gr2o_email';

        return $fieldList;
    }

    /**
     * Prepare for the import.
     *
     * @return RespondentTranslator (continuation pattern)
     */
    public function startImport(): RespondentTranslator
    {
        if ($this->targetModel instanceof DataWriterInterface) {
            $metaModel = $this->targetModel->getMetaModel();
            $options = $metaModel->get('grs_gender', 'multiOptions');
            $options['F'] = 'V'; // Make sure the value V is accepted
            $metaModel->set('grs_gender', 'multiOptions', $options);
        }

        parent::startImport();

        return $this;
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
            $sql = "SELECT gr2o_id_user
                    FROM gems__respondent2org
                    WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?";

            $id = $this->resultFetcher->fetchOne($sql, [$row['gr2o_patient_nr'], $row['gr2o_id_organization']]);

            if ($id) {
                $row['grs_id_user']  = $id;
                $row['gr2o_id_user'] = $id;
            }
        }

        if (empty($row['gr2o_email'])) {
            $row['calc_email'] = 1;
        }

        return $row;
    }
}
