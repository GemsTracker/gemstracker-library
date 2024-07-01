<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Translator;

/**
 * @package    Gems
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
class SurveySettingsImportTranslator extends \Gems_Model_Translator_StraightTranslator
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil_Model_ModelException
     */
    public function getFieldsTranslations()
    {
        $fieldList = [
            'gsu_export_code', 'gsu_survey_name', 'gsu_external_description', 'gsu_survey_description', 'gsu_surveyor_id',
            'gsu_active', 'gsu_id_primary_group', 'gsu_answers_by_group', 'gsu_answer_groups', 'gsu_allow_export',
            'gsu_mail_code', 'gsu_insertable', 'gsu_valid_for_length', 'gsu_valid_for_unit', 'gsu_duration', 'gsu_code',
            'gsu_beforeanswering_event', 'gsu_completed_event', 'gsu_display_event',
            ];

        return array_combine($fieldList, $fieldList);
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


        return $row;
    }
}