<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Export\ModelSource;

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class AnswerExportModelSource extends \Gems\Export\ModelSource\ExportModelSourceAbstract
{
    /**
     *
     * @var \Gems\Model\RespondentModel
     */
    private $_respModel;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * Current filter
     *
     * @var array
     */
    protected $filter;

    /**
     *
     * @var \Gems\Form
     */
    protected $form;

    /**
     * @var \Gems\Loader
     */
	protected $loader;

    /**
     * @var \Zend_Locale
     */
	protected $locale;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Add the default fields and optionally joins to the model
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addDefaultFieldsToExportModel(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if (!$model->checkJoinExists('gems__respondent2org.gr2o_id_user', 'gems__tokens.gto_id_respondent')) {
            $model->addTable('gems__respondent2org', array(
                'gems__respondent2org.gr2o_id_user' => 'gems__tokens.gto_id_respondent',
                'gems__respondent2org.gr2o_id_organization' => 'gems__tokens.gto_id_organization'), 'gr2o'
            );
        }

        if (!$model->checkJoinExists('gems__respondent2track.gr2t_id_respondent_track', 'gems__tokens.gto_id_respondent_track')) {
            $model->addTable('gems__respondent2track', array('gems__respondent2track.gr2t_id_respondent_track' => 'gems__tokens.gto_id_respondent_track'), 'gr2t');
        }
        if (!$model->checkJoinExists('gems__tracks.gtr_id_track', 'gems__tokens.gto_id_track')) {
            $model->addTable('gems__tracks', array('gems__tracks.gtr_id_track' => 'gems__tokens.gto_id_track'), 'gtr');
        }
        if (!$model->checkJoinExists('gems__consents.gco_description', 'gems__respondent2org.gr2o_consent')) {
            $model->addTable('gems__consents', array('gems__consents.gco_description' => 'gems__respondent2org.gr2o_consent'), 'gco');
        }
        
        if (!$model->checkJoinExists('gems__respondents.grs_id_user', 'gems__tokens.gto_id_respondent')) {
            $model->addTable('gems__respondents', array(
                'gems__respondents.grs_id_user' => 'gems__tokens.gto_id_respondent',
                ), 'grs');
        }

        $model->set('respondentid',        'label', $this->_('Respondent ID'), 'type', \MUtil\Model::TYPE_NUMERIC);
        $model->set('organizationid',      'label', $this->_('Organization'), 'type', \MUtil\Model::TYPE_NUMERIC,
            'multiOptions', $this->currentUser->getAllowedOrganizations()
        );

        // Add relation fields
        $model->set('gto_id_relation', 'label', $this->_('Relation ID'), 'type', \MUtil\Model::TYPE_NUMERIC);
        $model->set('forgroup', 'label', $this->_('Filler'), 'type', \MUtil\Model::TYPE_STRING);

        // Add Consent
        $model->set('consentcode',              'label', $this->_('Consent'), 'type', \MUtil\Model::TYPE_STRING);
        $model->set('resptrackid',              'label', $this->_('Respondent track ID'), 'type', \MUtil\Model::TYPE_NUMERIC);
        $model->set('gto_round_order',          'label', $this->_('Round order'));
        $model->set('gto_round_description',    'label', $this->_('Round description'));
        $model->set('gtr_track_name',           'label', $this->_('Track name'));
        $model->set('gr2t_track_info',          'label', $this->_('Track description'));

        // These are limesurvey fields, replace them with GemsTracker fields
        //$model->set('submitdate',               'label', $this->_('Submit date'));
        //$model->set('startdate',                'label', $this->_('Start date'));
        //$model->set('datestamp',                'label', $this->_('Datestamp'));
        $model->set('gto_completion_time',      'label', $this->_('Completion date'));
        $model->set('gto_start_time',           'label', $this->_('Start time'));

        $model->set('gto_valid_from',           'label', $this->_('Valid from'));
        $model->set('gto_valid_until',          'label', $this->_('Valid until'));
        $model->set('startlanguage',            'label', $this->_('Start language'));
        $model->set('lastpage',                 'label', $this->_('Last page'));

        $model->set('gto_id_token',                       'label', $this->_('Token'));
    }

    /**
     * Extensible function for added project specific data extensions
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraDataToExportModel(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        $this->_addExtraTokenReceptionCode($model, $data, $prefixes);
        $this->_addExtraTrackReceptionCode($model, $data, $prefixes);
        
        $this->_addExtraTrackFields($model, $data, $prefixes);        
        $this->_addExtraTrackFieldsByCode($model, $data, $prefixes);
        
        $this->_addExtraRespondentNumber($model, $data, $prefixes);
        $this->_addExtraGenderAge($model, $data, $prefixes);
    }

    /**
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraGenderAge(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if ($this->currentUser->hasPrivilege('pr.export.gender-age')) {
            if (isset($data['export_resp_gender']) && $data['export_resp_gender']) {
                $model->set('grs_gender', 'label', $this->getRespondentModel()->get('grs_gender', 'label'),
                        'type', \MUtil\Model::TYPE_STRING
                        );

                $prefixes['P'][] = 'grs_gender';
            }
            if (isset($data['export_birth_year']) && $data['export_birth_year']) {
                if (! $model->has('grs_birthyear')) {
                    $model->addColumn('YEAR(grs_birthday)', 'grs_birthyear');
                }
                $model->set('grs_birthyear', 'label', $this->_('Birth year'), 'type', \MUtil\Model::TYPE_NUMERIC);

                $prefixes['P'][] = 'grs_birthyear';
            }
            if (isset($data['export_birth_month']) && $data['export_birth_month']) {
                if (! $model->has('grs_birthmonth')) {
                    $model->addColumn('MONTH(grs_birthday)', 'grs_birthmonth');
                }
                $model->set('grs_birthmonth', 'label', $this->_('Birth month'), 'type', \MUtil\Model::TYPE_NUMERIC);

                $prefixes['P'][] = 'grs_birthmonth';
            }
            if (isset($data['export_birth_yearmonth']) && $data['export_birth_yearmonth']) {
                if (! $model->has('grs_birthyearmonth')) {
                    $model->addColumn("CONCAT(YEAR(grs_birthday), '/', LPAD(MONTH(grs_birthday), 2, '0'))", 'grs_birthyearmonth');
                }
                $model->set('grs_birthyearmonth', 'label', $this->_('Birth year/month'), 'type', \MUtil\Model::TYPE_STRING);

                $prefixes['P'][] = 'grs_birthyearmonth';
            }
        }
    }

    /**
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraRespondentNumber(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if ($this->currentUser->hasPrivilege('pr.export.add-resp-nr')) {
            if (isset($data['export_resp_nr']) && $data['export_resp_nr']) {
                $model->set('gr2o_patient_nr', 'label', $this->getRespondentModel()->get('gr2o_patient_nr', 'label'),
                        'type', \MUtil\Model::TYPE_STRING
                        );

                $prefixes['P'][] = 'gr2o_patient_nr';
            }
        }
    }

    /**
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraTokenReceptionCode(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if (isset($data['export_token_reception_code']) && $data['export_token_reception_code']) {
            $model->set('gto_reception_code', 'label', $this->_('Token reception code'));
            $prefixes['D'][] = 'gto_reception_code';
        }
    }

    /**
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraTrackFields(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if (isset($data['gto_id_track']) && $data['gto_id_track'] && isset($data['add_track_fields']) && $data['add_track_fields'] == 1) {
            $engine = $this->loader->getTracker()->getTrackEngine($data['gto_id_track']);
            $fieldNames = $engine->getFieldNames();
            if (!empty($fieldNames)) {
                $engine->addFieldsToModel($model, false, 'resptrackid');
                $prefixes['TF'] = array_keys($engine->getFieldNames());
            }
        }
    }

    /**
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraTrackFieldsByCode(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if (isset($data['export_trackfield_codes'])) {
            $includeCodes = array_map('trim', explode(',', $data['export_trackfield_codes']));
            $codes = [];
            
            foreach ($includeCodes as $name) {
                if (!empty($name)) {
                    $model->set($name, 'label', $name);
                    $prefixes['TF'][] = $name;
                    $codes[] = $name;
                }                    
            }
            
            if (!empty($codes)) {
                $tracker = $this->loader->getTracker();
                $transformer = new \Gems\Tracker\Model\AddTrackFieldsByCodeTransformer($tracker, $codes, 'resptrackid');
                $model->addTransformer($transformer);
            }
        }
    }

    /**
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addExtraTrackReceptionCode(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if (isset($data['export_track_reception_code']) && $data['export_track_reception_code']) {
            $model->set('gr2t_reception_code', 'label', $this->_('Track reception code'));
            $prefixes['TF'][] = 'gr2t_reception_code';
        }
    }

    /**
     * Add manual order of fields
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addManualFields(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        if (isset($data['manualFields'])) {
            $manualFields = $data['manualFields'];
            if (!is_array($data['manualFields'])) {
                $manualFields = explode(',', $manualFields);
            }

            $labels = $model->getCol('label');
            // Reset labels and order
            foreach($model->getItemNames() as $itemName) {
                $model->remove($itemName, 'label');
            }
            $model->resetOrder();

            $addedAnswers = false;
            foreach($manualFields as $field) {
                $field = trim($field);
                if ($field == '{answers}') {
                    foreach($prefixes['A'] as $field) {
                        if ($label = $labels[$field]) {
                            $model->set($field, 'label', $label);
                        }
                    }
                    $addedAnswers = true;
                    continue;
                }
                $label = $field;
                if (isset($labels[$field])) {
                    $label = $labels[$field];
                }
                $model->set($field, 'label', $label);
            }
            if (!$addedAnswers) {
                foreach ($prefixes['A'] as $field) {
                    if ($label = $labels[$field]) {
                        $model->set($field, 'label', $label);
                    }
                }
            }
        }
    }

    /**
     * Add nested model fields to the export model
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $data
     * @param array $prefixes
     */
    protected function _addNestedFieldsToExportModel(\MUtil\Model\ModelAbstract $model, array $data, array &$prefixes)
    {
        // Set labels in the main model for the submodel fields
        if ($model->getMeta('nested', false)) {
            $nestedNames = $model->getMeta('nestedNames');
            foreach($nestedNames as $nestedName) {
                $nestedModel = $model->get($nestedName, 'model');
                $nestedLabels = $nestedModel->getcolNames('label');
                foreach($nestedLabels as $colName) {
                    $label = $nestedModel->get($colName, 'label');
                    $model->set($colName, 'label', $label);
                }
                $model->remove($nestedName, 'label');
            }
        }
    }

    /**
     * Add all survey answers to the export model
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Tracker\Survey $survey
     * @param array $data
     * @param array $prefixes
     */
    protected function _addSurveyAnswersToExportModel(\MUtil\Model\ModelAbstract $model, \Gems\Tracker\Survey $survey, array $data, array &$prefixes)
    {
        $prefixes['A'] = [];
        $language = $this->locale->getLanguage();
        $questions = $survey->getQuestionList($language);
        $questionInformation = $survey->getQuestionInformation($language);
        foreach($questions as $questionName => $label) {
            if ($parent = $model->get($questionName, 'parent_question')) {
                if ($model->get($parent, 'type') === \MUtil\Model::TYPE_NOVALUE) {
                    if (isset($data['subquestions']) && $data['subquestions'] == 'prefix_child') {
                        $cleanLabel = strip_tags($label);
                        $model->set($questionName, 'label', $cleanLabel);
                    }
                    if (isset($data['subquestions']) && $data['subquestions'] == 'show_parent') {
                        if (!in_array($parent, $prefixes['A'])) {
                            $prefixes['A'][] = $parent;
                            if (isset($questionInformation[$parent], $questionInformation[$parent]['question'])) {
                                $cleanLabel = strip_tags($questionInformation[$parent]['question']);
                                $model->set($parent, 'label', $cleanLabel);
                            }
                        }
                    } else {
                        $model->remove($parent, 'label');
                    }
                }
            }

            if ($question = $model->get($questionName, 'survey_question') && $model->get($questionName, 'label') == null) {
                $model->set($questionName, 'label', $questionName);
                if (isset($questionInformation[$questionName], $questionInformation[$questionName]['question'])) {
                    $cleanLabel = strip_tags($questionInformation[$questionName]['question']);
                    $model->set($questionName, 'label', $cleanLabel);
                }
            }
            $prefixes['A'][] = $questionName;
        }
    }

    /**
     * Add the survey source attributes to the export model that have not yet been set.
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param \Gems\Tracker\Survey $survey
     * @param array $data
     * @param array $prefixes
     * @throws \Gems\Exception
     */
    protected function _addSurveySourceAttributesToExportModel(\MUtil\Model\ModelAbstract $model, \Gems\Tracker\Survey $survey, array $data, array &$prefixes)
    {
        $source = $survey->getSource();
        $attributes = $source->getAttributes();
        $preExistingFields = $model->getColNames('label');
        $attributes = array_diff($attributes, $preExistingFields);

        foreach($attributes as $attribute) {
            $model->set($attribute, 'label', $attribute);
        }
    }

    /**
     * Creates a \Zend_Form_Element_Select
     *
     * If $options is a string it is assumed to contain an SQL statement.
     *
     * @param string $name  Name of the element
     * @param string $label Label for element
     * @param string $description Optional description
     * @return \Zend_Form_Element_Checkbox
     */
    protected function _createCheckboxElement($name, $label, $description = null)
    {
        if ($name && $label) {
            $element = $this->form->createElement('checkbox', $name);
            $element->setLabel($label);
            $element->getDecorator('Label')->setOption('placement', \Zend_Form_Decorator_Abstract::APPEND);

            if ($description) {
                $element->setDescription($description);
                $element->setAttrib('title', $description);
            }

            return $element;
        }
    }

	/**
	 * Get form elements for the specific Export
     *
	 * @param  \Gems\Form $form existing form type
	 * @param  array data existing options set in the form
	 * @return array of form elements
	 */
	public function getExtraDataFormElements(\Gems\Form $form, $data)
	{
        $this->form = $form;
        $elements   = [];

        if (isset($data['gto_id_track']) && $data['gto_id_track']) {
            $elements['add_track_fields'] = $this->_createCheckboxElement(
                    'add_track_fields',
                    $this->_('Track fields'),
                    $this->_('Add track fields to export')
                    );
        }
        if ($this->currentUser->hasPrivilege('pr.export.add-resp-nr')) {
            $elements['export_resp_nr'] = $this->_createCheckboxElement(
                    'export_resp_nr',
                    $this->getRespondentModel()->get('gr2o_patient_nr', 'label'),
                    $this->_('Add respondent nr to export')
                    );
        }
        if ($this->currentUser->hasPrivilege('pr.export.gender-age')) {
            $elements['export_resp_gender'] = $this->_createCheckboxElement(
                    'export_resp_gender',
                    $this->_('Respondent gender'),
                    $this->_('Add respondent gender to export')
                    );

            $elements['export_birth_year'] = $this->_createCheckboxElement(
                    'export_birth_year',
                    $this->_('Respondent birth year'),
                    $this->_('Add respondent birth year to export')
                    );

            $elements['export_birth_month'] = $this->_createCheckboxElement(
                    'export_birth_month',
                    $this->_('Respondent birth month'),
                    $this->_('Add respondent birth month to export')
                    );

            $elements['export_birth_yearmonth'] = $this->_createCheckboxElement(
                    'export_birth_yearmonth',
                    $this->_('Respondent birth year/month'),
                    $this->_('Add respondent birth year and month to export')
                    );
        }

        $elements['export_track_reception_code'] = $this->_createCheckboxElement(
            'export_track_reception_code',
            $this->_('Track reception code'),
            $this->_('Add reception code of track')
        );

        $elements['export_token_reception_code'] = $this->_createCheckboxElement(
            'export_token_reception_code',
            $this->_('Token reception code'),
            $this->_('Add reception code of token')
        );

        return $elements;
    }

    /**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil\Model\ModelAbstract
     */
	public function getModel($filter = array(), $data = array())
	{
        if ($filter !== $this->filter || !$this->model) {
            $this->filter = $filter;

    		$surveyId = $filter['gto_id_survey'];
            $language = $this->locale->getLanguage();

            $survey   = $this->loader->getTracker()->getSurvey($surveyId);
            $model = $survey->getAnswerModel($language);

            // Reset labels and order
            foreach($model->getItemNames() as $itemName) {
                $model->remove($itemName, 'label');
            }
            $model->resetOrder();

            $prefixes = [];

            $this->_addDefaultFieldsToExportModel($model, $data, $prefixes);
            $this->_addNestedFieldsToExportModel($model, $data, $prefixes);
            $this->_addSurveySourceAttributesToExportModel($model, $survey, $data, $prefixes);
            $this->_addSurveyAnswersToExportModel($model, $survey, $data, $prefixes);
            

            $prefixes['D'] = array_diff($model->getColNames('label'), $prefixes['A'], $model->getItemsFor('table', 'gems__respondent2org'));

            $this->_addExtraDataToExportModel($model, $data, $prefixes);
            
            if (isset($data['column_identifiers']) && $data['column_identifiers'] == 1) {

                foreach ($prefixes as $prefix => $prefixCategory) {
                    foreach($prefixCategory as $columnName) {
                        if ($label = $model->get($columnName, 'label')) {
                            $model->set($columnName, 'label', '(' . $prefix . ') ' . $label);
                        }
                    }
                }
            }

            $this->_addManualFields($model, $data, $prefixes);


            $this->model = $model;

            // Exclude external fields from sorting
            foreach($this->model->getItemsUsed() as $item) {
                if (!$this->model->get($item, 'table', 'column_expression')) {
                    $this->model->set($item, 'noSort', true);
                }
            }
        }

		return $this->model;
    }

    /**
     *
     * @return \Gems\Model\RespondentModel
     */
    protected function getRespondentModel()
    {
        if (! $this->_respModel) {
            $this->_respModel = $this->loader->getModels()->getRespondentModel(true);
        }

        return $this->_respModel;
    }
}