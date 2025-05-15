<?php

namespace Gems\Export\Db;

use Gems\Exception;
use Gems\Export\Exception\ExportException;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Model\Respondent\RespondentModel;
use Gems\Tracker;
use Gems\Tracker\Model\AddTrackFieldsByCodeTransformer;
use Gems\Tracker\Survey;
use Gems\Tracker\SurveyModel;
use Psr\Container\ContainerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;


class AnswerModelContainer extends ModelContainer
{
    protected array $models = [];

    public function __construct(
        protected readonly Tracker $tracker,
        protected readonly Locale $locale,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly TranslatorInterface $translator,
        protected readonly RespondentModel $respondentModel,
    ) {
    }

    public function get(string $id, array $filter = [], array $applyFunctions = []): SurveyModel
    {
        if (!isset($filter['gto_id_survey'])) {
            throw new ExportException('No Survey ID specified');
        }

        $hash = $id . '_' . md5(json_encode($filter));
        if (isset($this->models[$hash])) {
            return $this->models[$hash];
        }

        $model = $this->createModel((int)$id, $filter);
        foreach($applyFunctions as $applyFunction) {
            if (method_exists($model, $applyFunction)) {
                $model->$applyFunction();
            }
        }

        $this->models[$hash] = $model;
        return $this->models[$hash];
    }

    public function has(string $id): bool
    {
        return true;
    }

    protected function createModel(int $id, array $filter): SurveyModel
    {
        $survey = $this->tracker->getSurvey($id);
        $language = $this->locale->getLanguage();

        /**
         * @var SurveyModel $model
         */
        $model = $survey->getAnswerModel($language);
        $metaModel = $model->getMetaModel();

        $data = [];
        foreach ($filter as $field => $value) {
            if (! (is_int($field) || $metaModel->has($field))) {
                $data[$field] = $value;
                unset($filter[$field]);
            }
        }

        // Reset labels and order
        foreach ($metaModel->getItemNames() as $itemName) {
            $metaModel->remove($itemName, 'label');
        }
        $metaModel->resetOrder();

        $prefixes = [];
        $this->addDefaultFieldsToExportModel($model, $data, $prefixes);
        $this->addNestedFieldsToExportModel($model, $data, $prefixes);

        $this->addSurveyAnswersToExportModel($model, $survey, $data, $prefixes);

        $prefixes['D'] = array_diff(
            $metaModel->getColNames('label'),
            $prefixes['A'],
            $metaModel->getItemsFor('table', 'gems__respondent2org')
        );

        $this->addExtraDataToExportModel($model, $data, $prefixes);

        if (isset($data['column_identifiers']) && $data['column_identifiers'] == 1) {
            foreach ($prefixes as $prefix => $prefixCategory) {
                foreach ($prefixCategory as $columnName) {
                    if ($label = $metaModel->get($columnName, 'label')) {
                        $metaModel->set($columnName, [
                            'label' => '(' . $prefix . ') ' . $label
                        ]);
                    }
                }
            }
        }

        $this->addManualFields($model, $data, $prefixes);

        // Exclude external fields from sorting
        foreach ($metaModel->getItemsUsed() as $item) {
            if (!$metaModel->get($item, 'table', 'column_expression')) {
                $metaModel->set($item, [
                    'noSort' => true,
                ]);
            }
        }

        return $model;
    }

    /**
     * Add the default fields and optionally joins to the model
     */
    protected function addDefaultFieldsToExportModel(SurveyModel $model, array $data, array &$prefixes): void
    {
        $metaModel = $model->getMetaModel();

        $joinStore = $model->getJoinStore();

        if (!$joinStore->hasTable('gems__respondent2org')) {
            $model->addTable(
                'gems__respondent2org',
                [
                    'gems__respondent2org.gr2o_id_user' => 'gems__tokens.gto_id_respondent',
                    'gems__respondent2org.gr2o_id_organization' => 'gems__tokens.gto_id_organization'
                ],
                false
            ); // 'gr2o'
        }

        if (!$joinStore->hasTable('gems__respondent2track')) {
            $model->addTable(
                'gems__respondent2track',
                ['gems__respondent2track.gr2t_id_respondent_track' => 'gems__tokens.gto_id_respondent_track'],
                false
            ); // 'gr2t'
        }
        if (!$joinStore->hasTable('gems__tracks')) {
            $model->addTable(
                'gems__tracks',
                ['gems__tracks.gtr_id_track' => 'gems__tokens.gto_id_track'],
                false
            ); // 'gtr'
        }
        if (!$joinStore->hasTable('gems__consents')) {
            $model->addTable(
                'gems__consents',
                ['gems__consents.gco_description' => 'gems__respondent2org.gr2o_consent'],
                false
            ); // 'gco'
        }

        if (!$joinStore->hasTable('gems__respondents')) {
            $model->addTable('gems__respondents', [
                'gems__respondents.grs_id_user' => 'gems__tokens.gto_id_respondent',
            ], false); // 'grs'
        }

        $metaModel->set('gto_id_respondent', [
            'label' => $this->translator->_('Respondent ID'),
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);

        $metaModel->set('gto_id_organization', [
            'label' => $this->translator->_('Organization'),
            'type' => MetaModelInterface::TYPE_NUMERIC,
            'multiOptions' => $this->currentUserRepository->getAllowedOrganizations(),
        ]);

        // Add relation fields
        $metaModel->set('gto_id_relation', [
            'label' => $this->translator->_('Relation ID'),
            'type' => MetaModelInterface::TYPE_NUMERIC
        ]);
        $metaModel->set('forgroup', [
            'label' => $this->translator->_('Filler'),
            'type' => MetaModelInterface::TYPE_STRING
        ]);

        // Add Consent
        $metaModel->set('gco_code', [
            'label' => $this->translator->_('Consent'),
            'type' => MetaModelInterface::TYPE_STRING,
        ]);
        $metaModel->set('gto_id_respondent_track', [
            'label' => $this->translator->_('Respondent track ID'),
            'type' => MetaModelInterface::TYPE_NUMERIC,
        ]);
        $metaModel->set('gto_round_order', [
            'label' => $this->translator->_('Round order'),
        ]);
        $metaModel->set('gto_round_description', [
            'label' => $this->translator->_('Round description')
        ]);
        $metaModel->set('gtr_track_name', [
            'label' => $this->translator->_('Track name'),
        ]);
        $metaModel->set('gr2t_track_info', [
            'label' => $this->translator->_('Track description'),
        ]);

        $metaModel->set('gto_completion_time', [
            'label' => $this->translator->_('Completion date'),
        ]);
        $metaModel->set('gto_start_time', [
            'label' => $this->translator->_('Start time'),
        ]);

        $metaModel->set('gto_valid_from', [
            'label' => $this->translator->_('Valid from'),
        ]);
        $metaModel->set('gto_valid_until', [
            'label' => $this->translator->_('Valid until'),
        ]);
        $metaModel->set('startlanguage', [
            'label' => $this->translator->_('Start language'),
            SqlRunnerInterface::NO_SQL => true,

        ]);
        $metaModel->set('lastpage', [
            'label' => $this->translator->_('Last page'),
            SqlRunnerInterface::NO_SQL => true,
        ]);

        $metaModel->set('gto_id_token', [
            'label' => $this->translator->_('Token'),
        ]);
    }

    /**
     * Extensible function for added project specific data extensions
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraDataToExportModel(SurveyModel $model, array $data, array &$prefixes): void
    {
        $this->addExtraTokenReceptionCode($model, $data, $prefixes);
        $this->addExtraTrackReceptionCode($model, $data, $prefixes);

        $this->addExtraTrackFields($model, $data, $prefixes);
        $this->addExtraTrackFieldsByCode($model, $data, $prefixes);

        $this->addExtraRespondentNumber($model, $data, $prefixes);
        $this->addExtraGenderAge($model, $data, $prefixes);
    }

    /**
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraGenderAge(SurveyModel $model, array $data, array &$prefixes): void
    {
        if ($this->currentUserRepository->getCurrentUser()->hasPrivilege('pr.export.gender-age')) {
            $metaModel = $model->getMetaModel();

            if (isset($data['export_resp_gender']) && $data['export_resp_gender']) {
                $metaModel->set('grs_gender', [
                    'label' => $this->respondentModel->getMetaModel()->get('grs_gender', 'label'),
                    'type' => MetaModelInterface::TYPE_STRING,
                ]);

                $prefixes['P'][] = 'grs_gender';
            }
            if (isset($data['export_birth_year']) && $data['export_birth_year']) {
                if (!$metaModel->has('grs_birthyear')) {
                    $model->addColumn('YEAR(grs_birthday)', 'grs_birthyear');
                }
                $metaModel->set('grs_birthyear', [
                    'label' => $this->translator->_('Birth year'),
                    'type' => MetaModelInterface::TYPE_NUMERIC,
                ]);

                $prefixes['P'][] = 'grs_birthyear';
            }
            if (isset($data['export_birth_month']) && $data['export_birth_month']) {
                if (!$metaModel->has('grs_birthmonth')) {
                    $model->addColumn('MONTH(grs_birthday)', 'grs_birthmonth');
                }
                $metaModel->set('grs_birthmonth', [
                    'label' => $this->translator->_('Birth month'),
                    'type' => MetaModelInterface::TYPE_NUMERIC,
                ]);

                $prefixes['P'][] = 'grs_birthmonth';
            }
            if (isset($data['export_birth_yearmonth']) && $data['export_birth_yearmonth']) {
                if (!$metaModel->has('grs_birthyearmonth')) {
                    $model->addColumn(
                        "CONCAT(YEAR(grs_birthday), '/', LPAD(MONTH(grs_birthday), 2, '0'))",
                        'grs_birthyearmonth'
                    );
                }
                $metaModel->set('grs_birthyearmonth', [
                    'label' => $this->translator->_('Birth year/month'),
                    'type' => MetaModelInterface::TYPE_STRING,
                ]);

                $prefixes['P'][] = 'grs_birthyearmonth';
            }
        }
    }

    /**
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraRespondentNumber(SurveyModel $model, array $data, array &$prefixes)
    {
        $metaModel = $model->getMetaModel();

        if ($this->currentUserRepository->getCurrentUser()->hasPrivilege('pr.export.add-resp-nr')) {
            if (isset($data['export_resp_nr']) && $data['export_resp_nr']) {
                $metaModel->set('gr2o_patient_nr', [
                    'label' => $this->respondentModel->getMetaModel()->get('gr2o_patient_nr', 'label'),
                    'type' => MetaModelInterface::TYPE_STRING,
                ]);

                $prefixes['P'][] = 'gr2o_patient_nr';
            }
        }
    }


    /**
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraTokenReceptionCode(SurveyModel $model, array $data, array &$prefixes): void
    {
        $metaModel = $model->getMetaModel();

        if (isset($data['export_token_reception_code']) && $data['export_token_reception_code']) {
            $metaModel->set('gto_reception_code', [
                'label' => $this->translator->_('Token reception code'),
            ]);
            $prefixes['D'][] = 'gto_reception_code';
        }
    }

    /**
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraTrackFields(SurveyModel $model, array $data, array &$prefixes): void
    {
        if (isset($data['gto_id_track']) && $data['gto_id_track'] && isset($data['add_track_fields']) && $data['add_track_fields'] == 1) {
            $engine = $this->tracker->getTrackEngine($data['gto_id_track']);
            $fieldNames = $engine->getFieldNames();
            if (!empty($fieldNames)) {
                $engine->addFieldsToModel($model->getMetaModel(), false, 'resptrackid');
                $prefixes['TF'] = array_keys($engine->getFieldNames());
            }
        }
    }

    /**
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraTrackFieldsByCode(SurveyModel $model, array $data, array &$prefixes): void
    {
        $metaModel = $model->getMetaModel();

        if (isset($data['export_trackfield_codes'])) {
            $includeCodes = array_map('trim', explode(',', $data['export_trackfield_codes']));
            $codes = [];

            foreach ($includeCodes as $name) {
                if (!empty($name)) {
                    $metaModel->set($name, [
                        'label' => $name
                    ]);
                    $prefixes['TF'][] = $name;
                    $codes[] = $name;
                }
            }

            if (!empty($codes)) {
                $transformer = new AddTrackFieldsByCodeTransformer(
                    $this->tracker,
                    $codes,
                    'resptrackid'
                );
                $metaModel->addTransformer($transformer);
            }
        }
    }

    /**
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addExtraTrackReceptionCode(SurveyModel $model, array $data, array &$prefixes)
    {
        if (isset($data['export_track_reception_code']) && $data['export_track_reception_code']) {
            $metaModel = $model->getMetaModel();
            $metaModel->set('gr2t_reception_code', [
                'label' => $this->translator->_('Track reception code')
            ]);
            $prefixes['TF'][] = 'gr2t_reception_code';
        }
    }

    /**
     * Add manual order of fields
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addManualFields(SurveyModel $model, array $data, array &$prefixes): void
    {
        $metaModel = $model->getMetaModel();

        if (isset($data['manualFields'])) {
            $manualFields = $data['manualFields'];
            if (!is_array($data['manualFields'])) {
                $manualFields = explode(',', $manualFields);
            }

            $labels = $metaModel->getCol('label');
            // Reset labels and order
            foreach ($metaModel->getItemNames() as $itemName) {
                $metaModel->remove($itemName, 'label');
            }
            $metaModel->resetOrder();

            $addedAnswers = false;
            foreach ($manualFields as $field) {
                $field = trim($field);
                if ($field == '{answers}') {
                    foreach ($prefixes['A'] as $field) {
                        if ($label = $labels[$field]) {
                            $metaModel->set($field, [
                                'label' => $label
                            ]);
                        }
                    }
                    $addedAnswers = true;
                    continue;
                }
                $label = $field;
                if (isset($labels[$field])) {
                    $label = $labels[$field];
                }
                $metaModel->set($field, [
                    'label' => $label
                ]);
            }
            if (!$addedAnswers) {
                foreach ($prefixes['A'] as $field) {
                    if ($label = $labels[$field]) {
                        $metaModel->set($field, [
                            'label' => $label
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Add nested model fields to the export model
     *
     * @param SurveyModel $model
     * @param array $data
     * @param array $prefixes
     */
    protected function addNestedFieldsToExportModel(SurveyModel $model, array $data, array &$prefixes): void
    {
        $metaModel = $model->getMetaModel();

        // Set labels in the main model for the submodel fields
        if ($metaModel->getMeta('nested', false)) {
            $nestedNames = $metaModel->getMeta('nestedNames');
            foreach ($nestedNames as $nestedName) {
                $nestedModel = $metaModel->get($nestedName, 'model');
                $nestedLabels = $nestedModel->getcolNames('label');
                foreach ($nestedLabels as $colName) {
                    $label = $nestedModel->get($colName, 'label');
                    $metaModel->set($colName, [
                        'label' => $label
                    ]);
                }
                $metaModel->remove($nestedName, 'label');
            }
        }
    }

    /**
     * Add all survey answers to the export model
     *
     * @param SurveyModel $model
     * @param Survey $survey
     * @param array $data
     * @param array $prefixes
     */
    protected function addSurveyAnswersToExportModel(
        SurveyModel $model,
        Survey $survey,
        array $data,
        array &$prefixes
    ): void {
        $metaModel = $model->getMetaModel();

        $prefixes['A'] = [];
        $language = $this->locale->getLanguage();
        $questions = $survey->getQuestionList($language);
        $questionInformation = $survey->getQuestionInformation($language);
        $test1 = $metaModel->getCol('label');
        foreach ($questions as $questionName => $label) {
            if ($parent = $metaModel->get($questionName, 'parent_question')) {
                if ($metaModel->get($parent, 'type') === MetaModelInterface::TYPE_NOVALUE) {
                    if (isset($data['subquestions']) && $data['subquestions'] == 'prefix_child') {
                        $cleanLabel = strip_tags($label);
                        $metaModel->set($questionName, [
                            'label' => $cleanLabel
                        ]);
                    }
                    if (isset($data['subquestions']) && $data['subquestions'] == 'show_parent') {
                        if (!in_array($parent, $prefixes['A'])) {
                            $prefixes['A'][] = $parent;
                            if (isset($questionInformation[$parent], $questionInformation[$parent]['question'])) {
                                $cleanLabel = strip_tags($questionInformation[$parent]['question']);
                                $metaModel->set($parent, [
                                    'label' => $cleanLabel
                                ]);
                            }
                        }
                    } else {
                        $metaModel->remove($parent, 'label');
                    }
                }
            }

            if ($question = $metaModel->get($questionName, 'survey_question') && $metaModel->get(
                    $questionName,
                    'label'
                ) == null) {
                $metaModel->set($questionName, [
                    'label' => $questionName,
                ]);
                if (isset($questionInformation[$questionName], $questionInformation[$questionName]['question'])) {
                    $cleanLabel = strip_tags($questionInformation[$questionName]['question']);
                    $metaModel->set($questionName, [
                        'label' => $cleanLabel
                    ]);
                }
            }
            $prefixes['A'][] = $questionName;
        }
        $test2 = $metaModel->getCol('label');
    }

    /**
     * Add the survey source attributes to the export model that have not yet been set.
     *
     * @param SurveyModel $model
     * @param Survey $survey
     * @param array $data
     * @param array $prefixes
     * @throws Exception
     */
    protected function addSurveySourceAttributesToExportModel(
        SurveyModel $model,
        Survey $survey,
        array $data,
        array &$prefixes
    ): void {
        $metaModel = $model->getMetaModel();

        $source = $survey->getSource();
        $attributes = $source->getAttributes();
        $preExistingFields = $metaModel->getColNames('label');
        $attributes = array_diff($attributes, $preExistingFields);

        foreach ($attributes as $attribute) {
            $metaModel->set($attribute, [
                'label' => $attribute
            ]);
        }
    }
}