<?php

class Gems_Default_ExportAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    public $request;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Export\\AnswerAutosearchFormSnippet');

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = array('Generic\\CurrentButtonRowSnippet', 'Export\\ExportSnippet');

    protected $exportModelSource = 'AnswerExportModelSource';

    protected function createModel($detailed, $action)
    {
        $this->data = $this->request->getPost();
        $this->getSearchFilter();

        if (isset($this->_searchFilter['gto_id_survey']) && is_numeric($this->_searchFilter['gto_id_survey'])) {
            // Surveys have been selected       
            $exportModelSource = $this->loader->getExportModelSource($this->exportModelSource);         
            $model = $exportModelSource->getModel($this->_searchFilter, $this->data);
            //\MUtil_Echo::track($model->loadFirst($filter));
            
            $model->set('gto_id_token', 'noExport', true);
        } else {
            $basicArray = array('gto_id_survey', 'gto_id_track', 'gto_round_description', 'gto_id_organization', 'gto_start_date', 'gto_end_date', 'gto_valid_from', 'gto_valid_until');
            $model = new \Gems_Model_PlaceholderModel($basicArray);
        }

        return $model;
    }

    public function getModel()
    {
        $model = parent::getModel();

        $noExportColumns = $model->getColNames('noExport');
        foreach($noExportColumns as $colName) {
            $model->remove($colName, 'label');
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
