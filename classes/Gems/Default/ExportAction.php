<?php

class Gems_Default_ExportAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    public $request;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Gems_Snippets_Export_AnswerAutosearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Generic\\CurrentButtonRowSnippet', 'Gems_Snippets_Export_ExportSnippet');

    protected $exportModelSource = 'AnswerExportModelSource';

    protected function createModel($detailed, $action)
    {
        //\MUtil_Echo::track($this->data);
        $model = false;
        $this->data = $this->request->getPost();
        $this->getSearchFilter();

        if (isset($this->_searchFilter['gto_id_survey']) && is_numeric($this->_searchFilter['gto_id_survey'])) {
            // Surveys have been selected       
            $exportModelSource = $this->loader->getExportModelSource($this->exportModelSource);         
            $model = $exportModelSource->getModel($this->_searchFilter, $this->data);
            //\MUtil_Echo::track($model->loadFirst($filter));
        } else {
            $model = new \Gems_Model_JoinModel('exportSurveys', 'gems__surveys');
            $model->set('gsu_survey_name', array('label' => $this->_('Name')));
            $model->set('gsu_active', array('label' => $this->_('Active')));


        }

        return $model;
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = false)
    {
        $this->_searchFilter = array();

        $this->_searchFilter = parent::getSearchFilter($useRequest);

        $this->_searchFilter[] = 'gto_start_time IS NOT NULL';
        $this->_searchFilter[] = "gr2o_reception_code IN ('OK', 'manual', 'dead', 'refusing', 'losttofollowup', 'movedoutside', 'moved')";
        $this->_searchFilter[] = "gto_reception_code IN ('OK', 'manual', 'dead', 'refusing', 'losttofollowup', 'movedoutside')";
        $this->_searchFilter['gto_id_survey'] = 558;

        return $this->_searchFilter;
    }

    public function indexAction()
    {
        $batch = $this->loader->getTaskRunnerBatch('export_data');
        $batch->reset();

        $this->data['survey'] = $this->request->getParam('survey');

        parent::indexAction();
    }
}
