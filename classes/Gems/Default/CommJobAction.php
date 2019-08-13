<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Default_CommJobAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    protected $autofilterParameters = array(
        'extraSort'    => array('gcj_id_order' => SORT_ASC),
        'searchFields' => 'getSearchFields'
        );

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'ModelFormVariableFieldSnippet';

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
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Agenda\\AutosearchFormSnippet');

    protected $monitorParameters = array(
        'monitorJob' => 'getMailMonitorJob'
    );

    protected $monitorSnippets = 'MonitorSnippet';

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Query to get the round descriptions for options
     * @var string
     */
    protected $roundDescQuery = "SELECT gro_round_description, gro_round_description FROM gems__rounds WHERE gro_id_track = ? GROUP BY gro_round_description";

    /**
     * The automatically filtered result
     *
     * @param $resetMvc When true only the filtered resulsts
     */
    public function autofilterAction($resetMvc = true)
    {
        parent::autofilterAction($resetMvc);

        //If allowed, add the sort action
        if ($this->menu->findAllowedController($this->getRequest()->getControllerName(), 'sort')) {
            $buttons = $this->_helper->SortableTable('sort', 'id');
            // First element is the wrapper
            $this->html[0]->append($buttons);
        }
    }

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
        $dbLookup   = $this->util->getDbLookup();
        $dbTracks   = $this->util->getTrackData();
        $mailUtil   = $this->util->getMailJobsUtil();
        $unselected = array('' => '');

        $model = new \MUtil_Model_TableModel('gems__comm_jobs');

        // START START START START START START START START START START START START START START START START START
        \Gems_Model::setChangeFieldsByPrefix($model, 'gcj');
        $model->set('gcj_id_order',            'label', $this->_('Execution order'),
                'description', $this->_('Execution order of the communication jobs, lower numbers are executed first.'),
                'required', true
                );
        if ($detailed) {
            $model->set('gcj_id_order',        'validator', $model->createUniqueValidator('gcj_id_order'));

            if ($action == 'create') {
                // Set the default round order
                $newOrder = $this->db->fetchOne("SELECT MAX(gcj_id_order) FROM gems__comm_jobs");

                if ($newOrder) {
                    $model->set('gcj_id_order', 'default', $newOrder + 10);
                }
            }
        }
        $model->set('gcj_id_message',          'label', $this->_('Template'),
                'multiOptions', $unselected + $dbLookup->getCommTemplates('token')
                );

        // EXECUTION EXECUTION EXECUTION EXECUTION EXECUTION EXECUTION EXECUTION EXECUTION EXECUTION EXECUTION
        if ($detailed) {
            $html = \MUtil_Html::create()->h4($this->_('Execution'));
            $model->set('execution',
                    'default', $html,
                    'label', ' ',
                    'elementClass', 'html',
                    'value', $html
                    );
        }
        $activeOptions = [
            0 => $this->_('Disabled'),
            1 => $this->_('Automatic'),
            2 => $this->_('Manually')
        ];
        $model->set('gcj_active',              'label', $this->_('Execution method'),
                'multiOptions', $activeOptions, 'required', true,
                'description', $this->_('Manual jobs run only manually, but not during automatic jobs. Disabled jobs not even then. ')
                );

        if ($detailed) {
            $bulkProcessOptions = $mailUtil->getBulkProcessOptions();
        } else {
            $bulkProcessOptions = $mailUtil->getBulkProcessOptionsShort();
        }
        $model->set('gcj_process_method',      'label', $this->_('Processing Method'),
                'default', 'O',
                'description', $this->_('Only for advanced users'),
                'multiOptions', $bulkProcessOptions
                );
        $model->set('gcj_filter_mode',         'label', $this->_('Filter for'),
                'multiOptions', $unselected + $mailUtil->getBulkFilterOptions()
                );

        if ($detailed) {
            // Only show reminder fields when needed
            $switches = array(
                'R' => array(
                        'gcj_filter_days_between'     => array('elementClass' => 'Text', 'label' => $this->_('Days between reminders'),'description', $this->_('1 day means the reminder is send the next day')),
                        'gcj_filter_max_reminders'    => array('elementClass' => 'Text', 'label' => $this->_('Maximum reminders'))
                    ),
                'B' => array(
                        'gcj_filter_days_between'     => array('elementClass' => 'Text', 'label' => $this->_('Days before expiration'),'description', ''),
                    ),
                'E' => array(
                        'gcj_filter_days_between'     => array('elementClass' => 'Text', 'label' => $this->_('Days before expiration'),'description', ''),
                    ),
                );
            $model->addDependency(array('ValueSwitchDependency', $switches), 'gcj_filter_mode');

            $model->set('gcj_filter_days_between', 'label', '',
                    'elementClass', 'Hidden',
                    'required', true,
                    'validators[]', 'Digits'
                    );
            $model->set('gcj_filter_max_reminders','label', '',
                    'elementClass', 'Hidden',
                    'description', $this->_('1 means only one reminder will be send'),
                    'required', true,
                    'validators[]', 'Digits'
                    );
        }

        // SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER SENDER
        if ($detailed) {
            $html = \MUtil_Html::create()->h4($this->_('Sender'));
            $model->set('send_from',
                    'default', $html,
                    'label', ' ',
                    'elementClass', 'html',
                    'value', $html
                    );
        }
        $model->set('gcj_id_user_as', 'label', $this->_('By staff member'),
                'multiOptions', $unselected + $dbLookup->getActiveStaff(),
                'default', $this->currentUser->getUserId(),
                'description', $this->_('Used for logging and possibly from address.'))
                ;
        $fromMethods = $unselected + $mailUtil->getBulkFromOptions();
        $model->set('gcj_from_method', 'label', $this->_('From address used'),
                'multiOptions', $fromMethods
                );
        if ($detailed) {
            // Show other field only when last $fromMethod is select
            $switches = ['F' => ['gcj_from_fixed' => ['elementClass' => 'Text', 'label' => $this->_('From other')]]];
            $model->addDependency(array('ValueSwitchDependency', $switches), 'gcj_from_method');

            $model->set('gcj_from_fixed',  'label', '',
                    'elementClass', 'Hidden',
                    'validators[mail]', 'SimpleEmail'
                    );
        }


       // RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER RECEIVER
        if ($detailed) {
            $html = \MUtil_Html::create()->h4($this->_('Receiver'));
            $model->set('send_to',
                    'default', $html,
                    'label', ' ',
                    'elementClass', 'html',
                    'value', $html
                    );
        }
        $model->set('gcj_target', 'label', $this->_('Filler'),
                'default', 0,
                'multiOptions', $mailUtil->getBulkTargetOptions()
                );
        if ($detailed) {
            $model->set('gcj_to_method', 'multiOptions', $mailUtil->getBulkToOptions());
            $model->set('gcj_fallback_method', 'multiOptions', $fromMethods);
            $model->set('gcj_fallback_fixed', 'validators[mail]', 'SimpleEmail');

            $model->addDependency('CommJob\\Senderdependency');
        }

        // SURVEY SELECTION SURVEY SELECTION SURVEY SELECTION SURVEY SELECTION SURVEY SELECTION SURVEY SELECTION
        if ($detailed) {
            $html = \MUtil_Html::create()->h4($this->_('Survey selection'));
            $model->set('selection',
                    'default', $html,
                    'label', ' ',
                    'elementClass', 'html',
                    'value', $html
                    );
        }

        $anyTrack[''] = $this->_('(all tracks)');
        $model->set('gcj_id_track', 'label', $this->_('Track'),
                'multiOptions', $anyTrack + $dbTracks->getAllTracks(),
                'onchange', 'this.form.submit();'
                );

        $anyRound['']  = $this->_('(all rounds)');
        $defaultRounds = $anyRound + $dbTracks->getAllRoundDescriptions();
        $model->set('gcj_round_description', 'label', $this->_('Round'),
                'multiOptions', $defaultRounds,
                'variableSelect', array(
                    'source' => 'gcj_id_track',
                    'baseQuery' => $this->roundDescQuery,
                    'ajax' => array('controller' => 'comm-job', 'action' => 'roundselect'),
                    'firstValue' => $anyRound,
                    'defaultValues' => $defaultRounds,
                ));

        $anySurvey[''] = $this->_('(all surveys)');
        $model->set('gcj_id_survey', 'label', $this->_('Survey'),
                'multiOptions', $anySurvey + $dbTracks->getAllSurveys(true)
                );

        $organizations = $dbLookup->getOrganizations();
        $anyOrganization[''] = $this->_('(all organizations)');
        $model->set('gcj_id_organization',
                'multiOptions', $anyOrganization + $organizations);

        if ($detailed || count($organizations) > 1) {
            $model->set('gcj_id_organization', 'label', $this->_('Organization'));
        }

        return $model;
    }

    /**
     * Execute a single mail job
     */
    public function executeAction($preview = false)
    {
        $jobId = intval($this->getParam(\MUtil_Model::REQUEST_ID));

        $batch = $this->loader->getTaskRunnerBatch('commjob-execute-' . $jobId);
        $batch->setMessageLogFile($this->project->getCronLogfile());
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (!$batch->isLoaded() && !is_null(($jobId))) {
            $batch->addMessage(sprintf(
                    $this->_('Starting single %s mail job %s'),
                    $this->project->getName(),
                    $jobId
                    ));

            // Check for unprocessed tokens
            $tracker = $this->loader->getTracker();
            $tracker->loadCompletedTokensBatch($batch, null, $this->currentUser->getUserId());

            // We could skip this, but a check before starting the batch is better
            $sql = $this->db->select()->from('gems__comm_jobs', array('gcj_id_job'))
                    ->where('gcj_active > 0')
                    ->where('gcj_id_job = ?', $jobId);

            $job = $this->db->fetchOne($sql);

            if (!empty($job)) {
                $batch->addTask('Mail\\ExecuteMailJobTask', $job, null, null, $preview);
            } else {
                $batch->reset();
                $this->addMessage($this->_("Mailjob is inactive and won't be executed"), 'danger');
            }

            if ($preview === true) {
                $batch->autoStart = true;
            }
        }

        if ($batch->isFinished()) {
            // Add the messages to the view and forward
            $messages = $batch->getMessages(true);
            if (count($messages)) {
                $this->addMessage($messages, 'info');
            }
            $echo = array_filter(array_map('trim', preg_split('/<[^>]+>/', \MUtil_Echo::out())));
            if ($echo) {
                echo "\n\n================================================================\nECHO OUTPUT:\n\n";
                echo implode("\n", $echo);
            }
            $this->accesslog->logChange($this->getRequest(), $messages, $echo);

            $this->_reroute(array('action'=>'show'));
        }

        if ($preview === true) {
            $title = sprintf($this->_('Preview single mail job %s'), $jobId);
        } else {
            $title = sprintf($this->_('Executing single mail job %s'), $jobId);
        }

        $this->_helper->BatchRunner($batch, $title, $this->accesslog);
    }

    /**
     * Execute all mail jobs
     */
    public function executeAllAction()
    {
        $this->_helper->BatchRunner(
                $this->loader->getMailLoader()->getCronBatch('commjob-execute-all'),
                $this->_('Execute all mail jobs'),
                $this->accesslog
                );
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Automatic mail jobs');
    }

    public function getMailMonitorJob()
    {
        return $this->loader->getUtil()->getMonitor()->getCronMailMonitor();
    }

    /**
     * Returns the fields for autosearch with
     *
     * @return array
     */
    public function getSearchFields()
    {
        return [
            'gcj_active' => $this->_('(all execution methods)')
        ];
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('automatic mail job', 'automatic mail jobs', $count);
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $lock = $this->util->getCronJobLock();
        if ($lock->isLocked()) {
            $this->addMessage(sprintf($this->_('Automatic mails have been turned off since %s.'), $lock->getLockTime()));

            if ($menuItem = $this->menu->findController('cron', 'cron-lock')) {
                $menuItem->set('label', $this->_('Turn Automatic Mail Jobs ON'));
            }
        }

        parent::indexAction();

        $this->html->pInfo($this->_('With automatic mail jobs and a cron job on the server, mails can be sent without manual user action.'));
    }

    public function monitorAction() {
        if ($this->monitorSnippets) {
            $params = $this->_processParameters($this->monitorParameters);

            $this->addSnippets($this->monitorSnippets, $params);
        }
    }

    /**
     * Execute a single mail job
     */
    public function previewAction() {
        $this->executeAction(true);
    }

    /**
     * Ajax return function for round selection
     */
    public function roundselectAction()
    {
        \Zend_Layout::resetMvcInstance();
        $trackId = $this->getRequest()->getParam('sourceValue');
        $rounds = $this->db->fetchPairs($this->roundDescQuery, $trackId);
        echo json_encode($rounds);
    }

    public function showAction()
    {
        parent::showAction();

        $jobId = $this->getRequest()->getParam('id');
        if (!is_null($jobId)) {
            $jobId = (int) $jobId;
            $job   = $this->db->fetchRow("SELECT * FROM gems__comm_jobs WHERE gcj_id_job = ?", $jobId);

            // Show a different color when not active,
            switch ($job['gcj_active']) {
                case 0:
                    $class   = ' disabled';
                    $caption = $this->_('Mailjob inactive, can not be sent');
                    break;

                case 2:
                    $class = ' manual';
                    $caption = $this->_('Mailjob manual, can only be sent using run');
                    break;

                // gcj_active = 1
                default:
                    $class = '';
                    $caption = $this->_('Mailjob automatic, can be sent using run or run all');
                    break;
            }
            $model  = $this->loader->getTracker()->getTokenModel();
            $filter = $this->loader->getUtil()->getMailJobsUtil()->getJobFilter($job);
            // Clone request and unset the id parameter to prevent filtering
            $cleanReq = clone $this->getRequest();
            $cleanReq->setParam(\MUtil_Model::REQUEST_ID, null);
            $params = [
                'request'         => $cleanReq,
                'model'           => $model,
                'filter'          => $filter,
                'showActionLinks' => false,
                'class'           => 'browser table mailjob' . $class,
                'caption'         => $caption,
                'onEmpty'         => $this->_('No tokens found to email'),
                'extraSort'       => ['gto_valid_from' => SORT_ASC, 'gto_round_order' => SORT_ASC]
            ];
            $this->addSnippet('TokenPlanTableSnippet', $params);
        }
    }

    public function sortAction()
    {
        $this->_helper->getHelper('SortableTable')->ajaxAction('gems__comm_jobs','gcj_id_job', 'gcj_id_order');
    }
}
