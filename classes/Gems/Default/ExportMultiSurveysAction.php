<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_ExportMultiSurveysAction extends \Gems_Default_ExportSurveyActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialisation
     */
    protected $autofilterParameters = array(
        'containingId'      => null,
        'exportModelSource' => 'getExportModelSource',
        'extraSort'         => 'gto_start_time ASC',
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = array('Export\\MultiSurveysSearchFormSnippet');

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet');

    /**
     * Performs the export step
     */
    protected function exportBatch($data)
    {
        $filter = $this->getSearchFilter();

        if ($data) {
            $batch = $this->loader->getTaskRunnerBatch('export_surveys');

            $models = $this->getExportModels($data['gto_id_survey'], $filter, $data);

            $batch->setVariable('model', $models);

            if (!$batch->count()) {
                $batch->minimalStepDurationMs = 2000;
                $batch->finishUrl = $this->view->url(array('step' => 'download'));

                $batch->setSessionVariable('files', array());

                foreach ($data['gto_id_survey'] as $surveyId) {
                    $batch->addTask('Export_ExportCommand', $data['type'], 'addExport', $data, $surveyId);
                }

                $batch->addTask('addTask', 'Export_ExportCommand', $data['type'], 'finalizeFiles');

                $batch->autoStart = true;
                $this->accesslog->logChange($this->getRequest(), null, $data + $filter);
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

    /**
     * Performs the download step
     */
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

    /**
     *
     * @return array
     * /
    protected function getExportData()
    {
        $data = $this->request->getPost();
        $exportSession = new \Zend_Session_Namespace(get_class($this));

        if ($data) {
            $exportSession->data = $data;
            $exportData = $data;
        } elseif (isset($exportSession->data)) {
            $exportData = $exportSession->data;
        } else {
            $exportData = [];
        }

        return $exportData;
    }

    /**
     *
     * @param \Gems_Export_ModelSource_ExportModelSourceAbstract $exportModelSource
     * @param array $filter
     * @param array $data
     * @return array
     */
    protected function getAnswerModel(\Gems_Export_ModelSource_ExportModelSourceAbstract $exportModelSource, array $filter, array $data)
    {
        $model = $exportModelSource->getModel($filter, $data);
        $noExportColumns = $model->getColNames('noExport');
        foreach($noExportColumns as $colName) {
            $model->remove($colName, 'label');
        }
        $model->applyParameters($filter, true);

        $model->addSort($this->autofilterParameters['extraSort']);

        return $model;
    }

    /**
     *
     * @param array $surveys
     * @param array $filter
     * @param array $data
     * @return array
     */
    protected function getExportModels(array $surveys, array $filter, array $data)
    {
        $models = array();
        $exportModelSource = $this->getExportModelSource();

        foreach($surveys as $surveyId) {
            $currentFilter = $filter;
            $currentFilter['gto_id_survey'] = $surveyId;

            $models[$surveyId] = $this->getAnswerModel($exportModelSource, $currentFilter, $data);
        }

        return $models;
    }

    /**
     *
     * @return type
     * /
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

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Export data from multiple surveys');
    }

    /**
     *
     */
    public function indexAction()
    {
        $step = $this->getParam('step') ?: ($this->request->isXmlHttpRequest() ? 'batch' : 'form');

        // \MUtil_Echo::track($step, $this->request->getParams());
        if ($step == 'form') {
            $batch = $this->loader->getTaskRunnerBatch('export_surveys');
            $batch->reset();
            parent::indexAction();

        } elseif ($step != 'download') {
            $data = $this->getSearchData();
            if ($data) {
                if (!isset($data['gto_id_survey']) || empty($data['gto_id_survey'])) {
                    $this->addMessage($this->_('Please select a survey to start the export'), 'danger');
                    parent::indexAction();

                } else {
                    $this->exportBatch($data);
                }
            } else {
                $this->exportBatch($data);
            }
        } elseif ($step == 'download') {
            $this->exportDownload();
        }
    }
}
