<?php



class Gems_Default_DatabaseBackupAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    protected $defaultSearchData = [
        'include_drop' => 1,
        'single_transaction' => 1,
    ];

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Database\\DatabaseExportFormSnippet');

    /**
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Tradition way of setting default sort (still in use)
     *
     * @var array
     */
    public $sortKey = array('group' => SORT_ASC, 'type' => SORT_ASC, 'name' => SORT_ASC);

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
    public function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getExportDbaModel($this->db, $this->escort->getDatabasePaths());
        if ($this->project->databaseFileEncoding) {
            $model->setFileEncoding($this->project->databaseFileEncoding);
        }
        $model->set('name',             'label', $this->_('Name'));

        //$model->set('exportTable','label', $this->_('Table'), 'formatFunction', [$this, 'visualBoolean']);
        $model->set('respondentData',   'label', $this->_('Respondent data'), 'formatFunction', [$this, 'visualBoolean']);

        $model->set('exportTable',      'label', $this->_('Export table'), 'formatFunction', [$this, 'visualBoolean']);
        $model->set('data',             'label', $this->_('Export data'), 'formatFunction', [$this, 'visualBoolean']);


        return $model;
    }

    /**
     * Action for the actual backup of the database
     */
    public function backupAction()
    {
        $filter = $this->getSearchFilter(false);
        $model = $this->getModel();

        $tables = $model->load();
        $allTables = [];
        $noDataTables = [];

        $addDropTables = false;
        if (array_key_exists('include_drop', $filter) && $filter['include_drop'] == '1') {
            $addDropTables = true;
        }
        $singleTransaction = false;
        if (array_key_exists('single_transaction', $filter) && $filter['single_transaction'] == '1') {
            $singleTransaction = true;
        }
        $lockTables = false;
        if (array_key_exists('lock_tables', $filter) && $filter['lock_tables'] == '1') {
            $lockTables = true;
        }

        foreach($tables as $table) {
            if ($table['type'] == 'view' && (!array_key_exists('include_views', $filter) || $filter['include_views'] != '1')) {
                continue;
            }

            $allTables[] = $table['name'];
            if ($table['respondentData'] === true && (!array_key_exists('include_respondent_data', $filter) || $filter['include_respondent_data'] != '1')) {
                $noDataTables[] = $table['name'];
            }
        }

        $filename = $this->getBackupFilename($filter);
        $dbConfig = $this->getDatabaseConfig();
        $dumpSettings = [
            'include-tables' => $allTables,
            'no-data' => $noDataTables,
            'single-transaction' => $singleTransaction,
            'lock-tables' => $lockTables,
            'add-locks' => false,
            'add-drop-table' => $addDropTables,
        ];

        ini_set('max_execution_time', 300);

        try {
            $dump = new \Ifsnop\Mysqldump\Mysqldump($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $dumpSettings);
            $dump->start($filename);
            echo sprintf($this->_('Database backup completed. File can be found in %s'), $filename);
        } catch (\Exception $e) {
            echo 'mysqldump-php error: ' . $e->getMessage();
        }
    }

    /**
     * Get the current database config, including a DSN
     *
     * @return array
     */
    protected function getDatabaseConfig()
    {
        $config = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $resources = $config->getOption('resources');
        $dbConfig = [
            'driver'   => $resources['db']['adapter'],
            'hostname' => $resources['db']['params']['host'],
            'database' => $resources['db']['params']['dbname'],
            'username' => $resources['db']['params']['username'],
            'password' => $resources['db']['params']['password'],
            'charset'  => $resources['db']['params']['charset'],
            'dsn'      => sprintf('mysql:host=%s;dbname=%s', $resources['db']['params']['host'], $resources['db']['params']['dbname']),
        ];

        return $dbConfig;
    }

    /**
     * Get the string name of the backup file.
     * With current settings this file will be written in /var/backup/gems_backup_YYYYMMDD.sql or
     * /var/backup/gems_backup_no_respondent_data_YYYYMMDD.sql if respondent data is not included
     *
     * @param $filter
     * @return string
     * @throws Zend_Exception
     */
    protected function getBackupFilename($filter)
    {
        $directory =  GEMS_ROOT_DIR . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'backup';
        \MUtil_File::ensureDir($directory);

        $directory .= DIRECTORY_SEPARATOR;

        $backupName = 'gems_backup_';
        if (!array_key_exists('include_respondent_data', $filter) || $filter['include_respondent_data'] == '1') {
            $backupName .= 'no_respondent_data_';
        }
        $now = new \DateTime();
        $backupName .= $now->format('Ymd');

        $backupName .= '.sql';

        return $directory . $backupName;
    }

    /**
     * Get the filter to use with the model for searching including model sorts, etc..
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter($useRequest = true)
    {
        $filter =  parent::getSearchFilter($useRequest);

        if (!array_key_exists('include_views', $filter) || $filter['include_views'] != '1') {
            $filter['type'] = 'table';
        }

        return $filter;
    }

    /**
     * Show a check or cross for true or false values
     *
     * @param bool $value
     * @return mixed
     */
    public function visualBoolean($value)
    {
        if ($value === true) {
            //return '<i>O</i>';
            return \MUtil_Html::create()->i(['class' => 'fa fa-check', 'style' => 'color: green;']);
        }
        return \MUtil_Html::create()->i(['class' => 'fa fa-times', 'style' => 'color: red;']);
    }


}