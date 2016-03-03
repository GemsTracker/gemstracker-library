<?php

class Gems_Default_BatchExportAction extends \Gems_Controller_ModelSnippetActionAbstract
{

	public $autofilterParameters = array(
		'browse' => false,
	);

    public $request;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Gems_Snippets_Export_SurveyAutosearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    //protected $indexStopSnippets = array('Generic\\CurrentButtonRowSnippet', 'Gems_Snippets_Export_ExportSnippet');

    protected function createModel($detailed, $action)
    {
        $model = new \Gems_Model_JoinModel('surveys', 'gems__surveys', 'gsu');
        //$model = new \Gems_Model_JoinModel('track', 'gems__tracks', 'gtr');
        /*$model->addRightTable('gems__rounds', array('gro_id_survey' => 'gsu_id_survey'));
        $model->addTable('gems__tracks', array('gro_id_track' => 'gtr_id_track'));*/

        $model->set('gsu_survey_name',        'label', $this->_('Name'));
        /*$model->set('gsu_id_survey',        'label', $this->_('Survey Id'));
        $model->set('gro_id_round',        'label', $this->_('Round Id'));
        $model->set('gro_id_track',        'label', $this->_('Track Id'));*/

        return $model;
    }

    
}
