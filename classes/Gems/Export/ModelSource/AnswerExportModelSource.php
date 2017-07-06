<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AnswerExportModelSource.php 2451 2015-03-09 18:03:25Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Export_ModelSource_AnswerExportModelSource extends \Gems_Export_ModelSource_ExportModelSourceAbstract
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Current filter
     *
     * @var array
     */
    protected $filter;

    /**
     * @var \Gems_Loader
     */
	protected $loader;

    /**
     * @var \Zend_Locale
     */
	protected $locale;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil_Model_ModelAbstract
     */
	public function getModel($filter = array(), $data = array())
	{
        if ($filter !== $this->filter || !$this->model) {

            $this->filter = $filter;

    		$surveyId = $filter['gto_id_survey'];
            $language    = $this->locale->getLanguage();

            $survey      = $this->loader->getTracker()->getSurvey($surveyId);
            $model = $survey->getAnswerModel($language);

            $source = $survey->getSource();
            $questions = $source->getFullQuestionList($language, $surveyId, $survey->getSourceSurveyId());
            foreach($questions as $questionName => $label ) {
                if ($parent = $model->get($questionName, 'parent_question')) {
                    if ($model->get($parent, 'type') === \MUtil_Model::TYPE_NOVALUE) {
                        if (isset($data['prefix_child']) && $data['prefix_child'] == 1) {
                            $cleanLabel = strip_tags($label);
                            $model->set($questionName, 'label', $cleanLabel);
                        }
                        if (isset($data['show_parent']) && $data['show_parent'] == 1) {
                            $model->remove($parent, 'label');
                        }
                    }
                }

                if ($question = $model->get($questionName, 'survey_question') && $model->get($questionName, 'label') == null) {
                    $model->set($questionName, 'label', $questionName);
                }

            }

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

            $prefixes = array();

            $prefixes['A'] = array_keys($questions);

            $attributes = $source->getAttributes();

            foreach($attributes as $attribute) {
                $model->set($attribute, 'label', $attribute);
            }

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

            $model->set('respondentid',        'label', $this->_('Respondent ID'), 'type', \MUtil_Model::TYPE_NUMERIC);
            $model->set('organizationid',      'label', $this->_('Organization'), 'type', \MUtil_Model::TYPE_NUMERIC,
                                                    'multiOptions', $this->currentUser->getAllowedOrganizations()
            );
            // Add Consent
            $model->set('consentcode',              'label', $this->_('Consent'), 'type', \MUtil_Model::TYPE_STRING);
            $model->set('resptrackid',              'label', $this->_('Respondent track ID'), 'type', \MUtil_Model::TYPE_NUMERIC);
            $model->set('gto_round_description',    'label', $this->_('Round description'));
            $model->set('gtr_track_name',           'label', $this->_('Track name'));
            $model->set('gr2t_track_info',          'label', $this->_('Track description'));

            $model->set('submitdate',               'label', $this->_('Submit date'));
            $model->set('startdate',                'label', $this->_('Start date'));
            $model->set('datestamp',                'label', $this->_('Datestamp'));
            $model->set('gto_valid_from',           'label', $this->_('Valid from'));
            $model->set('gto_valid_until',          'label', $this->_('Valid until'));
            $model->set('startlanguage',            'label', $this->_('Start language'));
            $model->set('lastpage',                 'label', $this->_('Last page'));

            $model->set('gto_id_token',                       'label', $this->_('Token'));

            $prefixes['D'] = array_diff($model->getItemNames(), $prefixes['A']);

            if (isset($data['gto_id_track']) && $data['gto_id_track'] && isset($data['add_track_fields']) && $data['add_track_fields'] == 1) {
            	$trackId = $filter['gto_id_track'];
            	$engine = $this->loader->getTracker()->getTrackEngine($trackId);
            	$engine->addFieldsToModel($model, false, 'gto_id_respondent_track');

                // Add relation fields
                $model->set('gto_id_relation', 'label', $this->_('Relation ID'), 'type', \MUtil_Model::TYPE_NUMERIC);
                $model->set('gtf_field_name', 'label', $this->_('Relation'), 'type', \MUtil_Model::TYPE_STRING);

                $prefixes['TF'] = array_diff($model->getItemNames(), $prefixes['A'], $prefixes['D']);
            }

            if (isset($data['column_identifiers']) && $data['column_identifiers'] == 1) {

                foreach ($prefixes as $prefix => $prefixCategory) {
                    foreach($prefixCategory as $columnName) {
                        if ($label = $model->get($columnName, 'label')) {
                            $model->set($columnName, 'label', '(' . $prefix . ') ' . $label);
                        }
                    }
                }
            }
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
}