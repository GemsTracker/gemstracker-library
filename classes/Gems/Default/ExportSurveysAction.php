<?php

class Gems_Default_ExportSurveysAction extends \MUtil_Controller_Action
{
    public $db;

    protected $exportFormSnippets = 'Export\\ExportSurveysFormSnippet';

    protected $exportModelSource = 'AnswerExportModelSource';

    protected $exportDefaultSorts = array('gto_start_time');

    protected function exportBatch()
    {
        $filter = $this->getFilter($this->data);

        if ($this->data) {

            $batch = $this->loader->getTaskRunnerBatch('export_surveys');

            $models = $this->getExportModels($this->data['gto_id_survey'], $filter);

            $batch->setVariable('model', $models);

            if (!$batch->count()) {
                $batch->minimalStepDurationMs = 2000;
                $batch->finishUrl = $this->view->url(array('step' => 'download'));

                $batch->setSessionVariable('files', array());

                foreach($this->data['gto_id_survey'] as $surveyId) {
                    $batch->addTask('Export_ExportCommand', $this->data['type'], 'addExport', $this->data, $surveyId);
                }

                $batch->addTask('addTask', 'Export_ExportCommand', $this->data['type'], 'finalizeFiles');

                $batch->autoStart = true;
            }

                        

            if ($batch->run($this->request)) {
                exit;
            } else {
                $controller = $this;

                if ($batch->isFinished()) {
                } else {
                    if ($batch->count()) {
                        $controller->html->append($batch->getPanel($controller->view, $batch->getProgressPercentage() . '%'));
                    } else {
                        $controller->html->pInfo($controller->_('Nothing to do.'));
                    }
                    $controller->html->pInfo()->a(
                            \MUtil_Html_UrlArrayAttribute::rerouteUrl($this->getRequest(), array('action'=>'index', 'step' => false)),
                            array('class'=>'actionlink'),
                            $this->_('Back')
                            );
                }
            }
        }
    }

    protected function exportDownload()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $batch = $this->loader->getTaskRunnerBatch('export_surveys');
        $file = $batch->getSessionVariable('file');
        foreach($file['headers'] as $header) {
            header($header);
        }
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($file['file']);
        // Now clean up the file
        unlink($file['file']);

        exit;
    }

    protected function getFilter()
    {
        $filter = array();
        if (isset($this->data['gto_id_track']) && $this->data['gto_id_track']) {
            $filter['gto_id_track'] = $this->data['gto_id_track'];
        }
        if (isset($this->data['gto_round_description']) && $this->data['gto_round_description']) {
            $filter['gto_round_description'] = $this->data['gto_round_description'];
        }
        if (isset($this->data['gto_id_organization']) && $this->data['gto_id_organization']) {
            $filter['gto_id_organization'] = $this->data['gto_id_organization'];
        }
        if (isset($this->data['dateused']) && $this->data['dateused']) {
            $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($this->data, $this->db);
            if ($where) {
                $filter[] = $where;
            }
        }

        $filter[] = 'gto_start_time IS NOT NULL';
        if (!isset($this->data['incomplete']) || !$this->data['incomplete']) {
            $filter[] = 'gto_completion_time IS NOT NULL';
        }

        $filter['gco_code'] = 'consent given';
        //$filter['gr2o_reception_code'] = 'OK';
        $filter['grc_success'] = 1;

        if (isset($this->data['ids'])) {
            $idStrings = $this->data['ids'];

            $idArray = preg_split('/[\s,;]+/', $idStrings, -1, PREG_SPLIT_NO_EMPTY);

            if ($idArray) {
                // Make sure output is OK
                // $idArray = array_map(array($this->db, 'quote'), $idArray);

                $filter['gto_id_respondent'] = $idArray;
            }
        }

        return $filter;
    }

    protected function getExportData()
    {
        $data = $this->request->getPost();
        $exportSession = new \Zend_Session_Namespace(get_class($this));

        $exportData = array();
        if ($data) {
            $exportSession->data = $data;
            $exportData = $data;
        } elseif (isset($exportSession->data)) {
            $exportData = $exportSession->data;
        }

        return $exportData;
    }

    protected function getExportModels($surveys, $filter)
    {
        $models = array();
        $exportModelSource = $this->loader->getExportModelSource($this->exportModelSource);

        foreach($surveys as $surveyId) {
            $currentFilter = $filter;
            $currentFilter['gto_id_survey'] = $surveyId;

            $models[$surveyId] = $this->getExportModel($exportModelSource, $currentFilter);
        }

        return $models;        
    }

    protected function getExportModel($exportModelSource, $filter)
    {
        $model = $exportModelSource->getModel($filter, $this->data);
        $noExportColumns = $model->getColNames('noExport');
        foreach($noExportColumns as $colName) {
            $model->remove($colName, 'label');
        }
        $model->applyParameters($filter, true);

        $model->addSort($this->exportDefaultSorts);

        return $model;
    }

    public function indexAction()
    {
        $this->initHtml();
        $step = $this->request->getParam('step');
        $this->data = $this->getExportData();

        if (!$step || $this->data && $step == 'form') {
            $this->addSnippet($this->exportFormSnippets);
            $batch = $this->loader->getTaskRunnerBatch('export_surveys');
            $batch->reset();
        } elseif ($step == 'batch') {
            if ($this->data) {
                if (!isset($this->data['gto_id_survey']) || empty($this->data['gto_id_survey'])) {
                    $this->addMessage($this->_('Please select a survey to start the export'), 'danger');
                    $this->addSnippet($this->exportFormSnippets);
                } else {
                    $this->exportBatch();
                }
            } else {
                $this->exportBatch();
            }
        } elseif ($step == 'download') {
            $this->exportDownload();
        }
    }

    /**
     * Intializes the html component.
     *
     * @param boolean $reset Throws away any existing html output when true
     * @return void
     */
    public function initHtml($reset = false)
    {
        if (! $this->html) {
            \Gems_Html::init();
        }

        parent::initHtml($reset);
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader()
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = $this->loader->getSnippetLoader($this);
    }
}
