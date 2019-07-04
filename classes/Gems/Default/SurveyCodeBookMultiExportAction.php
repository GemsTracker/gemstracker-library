<?php


class Gems_Default_SurveyCodeBookMultiExportAction extends \Gems_Default_ExportSurveyActionAbstract
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
    protected $autofilterSnippets = array('Export\\SurveyCodeBookMultiExportSnippet');

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
     * @param array $surveys
     * @param array $filter
     * @param array $data
     * @return array
     */
    protected function getExportModels(array $surveys, array $filter, array $data)
    {
        $models = array();

        foreach($surveys as $surveyId) {
            $models[$surveyId] = $this->getSurveyCodeBookModel($surveyId);
        }

        return $models;
    }

    protected function getSurveyCodeBookModel($surveyId)
    {
        return $this->loader->getModels()->getSurveyCodeBookModel($surveyId);

    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Export codebooks from multiple surveys');
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
