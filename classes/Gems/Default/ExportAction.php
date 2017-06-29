<?php

class Gems_Default_ExportAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var array
     */
    private $_searchFilter;

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
    protected $autofilterParameters = array('extraSort' => 'gto_start_time ASC');

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Export\\AnswerAutosearchFormSnippet');

    /**
     * Class for export model source
     *
     * @var string
     */
    protected $exportModelSource = 'AnswerExportModelSource';

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $filter = $this->getSearchFilter();

        if (isset($filter['gto_id_survey']) && is_numeric($filter['gto_id_survey'])) {
            // Surveys have been selected
            $exportModelSource = $this->loader->getExportModelSource($this->exportModelSource);
            $model = $exportModelSource->getModel($filter, $filter);

            $noExportColumns = $model->getColNames('noExport');
            foreach($noExportColumns as $colName) {
                $model->remove($colName, 'label');
            }

            $rightsExportColumns = $model->getCol('exportPrivilege');
            foreach($rightsExportColumns as $colName => $privilege) {
                $label = $model->get($colName, 'label');
                $model->remove($colName, 'label');

                if ($this->currentUser->hasPrivilege($privilege)) {
                    if ($label) {
                        $model->set($colName, 'exportOptionLabel', $label);
                    }
                } else {
                    // If set directly remove this label as it would mean it would be selectable
                    $model->remove($colName, 'exportOptionLabel');
                }
            }
        } else {
            $basicArray = array('gto_id_survey', 'gto_id_track', 'gto_round_description', 'gto_id_organization', 'gto_start_date', 'gto_end_date', 'gto_valid_from', 'gto_valid_until');

            $model = new \Gems_Model_PlaceholderModel('nosurvey', $basicArray);
            $model->set('gto_id_survey', 'label', $this->_('Please select a survey to start the export'));
        }

        return $model;
    }

    /**
     * Returns the model for the current $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @return \MUtil_Model_ModelAbstract
     */
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
        if (null !== $this->_searchFilter) {
            return $this->_searchFilter;
        }

        $this->_searchFilter = parent::getSearchFilter($useRequest);

        $this->_searchFilter[] = 'gto_start_time IS NOT NULL';
        if (!isset($this->_searchFilter['incomplete']) || !$this->_searchFilter['incomplete']) {
            $this->_searchFilter[] = 'gto_completion_time IS NOT NULL';
        }

        if (isset($this->_searchFilter['dateused']) && $this->_searchFilter['dateused']) {
            $where = \Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($this->_searchFilter, $this->db);
            if ($where) {
                $this->_searchFilter[] = $where;
            }
        }

        $this->_searchFilter['gco_code'] = 'consent given';
        $this->_searchFilter['grc_success'] = 1;

        if (isset($this->_searchFilter['ids'])) {
            $idStrings = $this->_searchFilter['ids'];

            $idArray = preg_split('/[\s,;]+/', $idStrings, -1, PREG_SPLIT_NO_EMPTY);

            if ($idArray) {
                $this->_searchFilter['gto_id_respondent'] = $idArray;
            }
        }

        return $this->_searchFilter;
    }

    /**
     * Action for selecting a survey to export
     */
    public function indexAction()
    {
        $batch = $this->loader->getTaskRunnerBatch('export_data');
        $batch->reset();

        // $this->data['survey'] = $this->request->getParam('survey');

        parent::indexAction();
    }
}
