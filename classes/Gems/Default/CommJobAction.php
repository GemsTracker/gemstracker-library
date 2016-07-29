<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
        'extraSort'   => array('gcj_id_order' => SORT_ASC)
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
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Query to get the round descriptions for options
     * @var string
     */
    protected $roundDescriptionQuery = "SELECT gro_round_description, gro_round_description FROM gems__rounds WHERE gro_id_track = ? GROUP BY gro_round_description";

    public function autofilterAction($resetMvc = true)
    {
        parent::autofilterAction($resetMvc);
        
        $buttons = $this->_helper->SortableTable('sort', 'id');

        // First element is the wrapper
        $this->html[0]->append($buttons);
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
        $translated = $this->util->getTranslated();
        $empty      = $translated->getEmptyDropdownArray();
        $unselected = array('' => '');

        $model = new \MUtil_Model_TableModel('gems__comm_jobs');

        \Gems_Model::setChangeFieldsByPrefix($model, 'gcj');
        $model->set('gcj_id_order',            'label', $this->_('Order'), 'description', $this->_('Execution order of the communication jobs, lower numbers are executed first.'));
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
        $model->set('gcj_id_message',          'label', $this->_('Template'), 'multiOptions', $unselected + $dbLookup->getCommTemplates('token'));
        $model->set('gcj_id_user_as',          'label', $this->_('By staff member'),
                'multiOptions', $unselected + $dbLookup->getActiveStaff(), 'default', $this->currentUser->getUserId(),
                'description', $this->_('Used for logging and possibly from address.'));
        $model->set('gcj_active',              'label', $this->_('Active'),
                'multiOptions', $translated->getYesNo(), 'elementClass', 'Checkbox', 'required', true,
                'description', $this->_('Job is only run when active.'));

        $fromMethods = $unselected + $this->getBulkMailFromOptions();
        $model->set('gcj_from_method',         'label', $this->_('From address used'), 'multiOptions', $fromMethods);
        if ($detailed) {
            // Show other field only when last $fromMethod is select
            end($fromMethods);  // Move array pointer to the end
            $lastKey   = key($fromMethods);
            $switches = array($lastKey => array( 'gcj_from_fixed' => array('elementClass' => 'Text', 'label' => $this->_('From other'))));

            $model->addDependency(array('ValueSwitchDependency', $switches), 'gcj_from_method');
            $model->set('gcj_from_fixed',      'label', '',
                    'elementClass', 'Hidden');
        }
        $model->set('gcj_process_method',      'label', $this->_('Processing Method'), 'default', 'O', 'multiOptions', $translated->getBulkMailProcessOptions());
        $model->set('gcj_filter_mode',         'label', $this->_('Filter for'), 'multiOptions', $unselected + $this->getBulkMailFilterOptions());

        if ($detailed) {
            // Only show reminder fields when needed
            $switches = array(
                'R' => array(
                        'gcj_filter_days_between'     => array('elementClass' => 'Text', 'label' => $this->_('Days between reminders')),
                        'gcj_filter_max_reminders'    => array('elementClass' => 'Text', 'label' => $this->_('Maximum reminders'))
                    )
                );
            $model->addDependency(array('ValueSwitchDependency', $switches), 'gcj_filter_mode');

            $model->set('gcj_filter_days_between', 'label', '',
                    'elementClass', 'Hidden',
                    'description', $this->_('1 day means the reminder is send the next day'),
                    'validators[]', 'Digits');
            $model->set('gcj_filter_max_reminders','label', '',
                    'elementClass', 'Hidden',
                    'description', $this->_('1 means only one reminder will be send'),
                    'validators[]', 'Digits');
        }

        // If you really want to see this information in the overview, uncomment for the shorter labels
        // $model->set('gcj_filter_days_between', 'label', $this->_('Interval'), 'validators[]', 'Digits');
        // $model->set('gcj_filter_max_reminders','label', $this->_('Max'), 'validators[]', 'Digits');

        $model->set('gcj_id_track',        'label', $this->_('Track'), 'multiOptions', $empty + $dbTracks->getAllTracks());

        $defaultRounds = $empty + $this->db->fetchPairs('SELECT gro_round_description, gro_round_description FROM gems__rounds WHERE gro_round_description IS NOT NULL AND gro_round_description != "" GROUP BY gro_round_description');
        $model->set('gcj_round_description',         'label', $this->_('Round'), 'multiOptions', $defaultRounds,
                    'variableSelect', array(
                        'source' => 'gcj_id_track',
                        'baseQuery' => $this->roundDescriptionQuery,
                        'ajax' => array('controller' => 'comm-job', 'action' => 'roundselect'),
                        'firstValue' => $empty,
                        'defaultValues' => $defaultRounds,
                    ));

        $model->set('gcj_id_survey',       'label', $this->_('Survey'), 'multiOptions', $empty + $dbTracks->getAllSurveys(true));

        if ($detailed) {
            $model->set('gcj_id_organization', 'label', $this->_('Organization'),
                    'multiOptions', $empty + $dbLookup->getOrganizations());
        }

        return $model;
    }

    /**
     * Execute a single mail job
     */
    public function executeAction()
    {
        $jobId = $this->getParam(\MUtil_Model::REQUEST_ID);
        $batch = $this->loader->getTaskRunnerBatch('commjob-execute-' . $jobId);
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (!$batch->isLoaded() && !is_null(($jobId))) {
            // Check for unprocessed tokens
            $tracker = $this->loader->getTracker();
            $tracker->processCompletedTokens(null, $this->currentUser->getUserId());

            // We could skip this, but a check before starting the batch is better
            $sql = $this->db->select()->from('gems__comm_jobs', array('gcj_id_job'))
                    ->where('gcj_active = 1')
                    ->where('gcj_id_job = ?', $jobId);

            $job = $this->db->fetchOne($sql);

            if (!empty($job)) {
                $batch->addTask('Mail\\ExecuteMailJobTask', $job);
            }
        }

        if ($batch->isFinished()) {
            // Add the messages to the view and forward
            $messages = $batch->getMessages(true);
            foreach ($messages as $message) {
                $this->addMessage($message);
            }

            $this->_reroute(array('action'=>'show'));
        }

        $this->_helper->BatchRunner($batch, $this->_('Execute single mail job'), $this->accesslog);
    }

    /**
     * Execute all mail jobs
     */
    public function executeAllAction()
    {
        $batch = $this->loader->getTaskRunnerBatch('commjob-execute-all');
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (!$batch->isLoaded()) {
            // Check for unprocessed tokens
            $tracker = $this->loader->getTracker();
            $tracker->processCompletedTokens(null, $this->currentUser->getUserId());

            $batch->addTask('Mail\\AddAllMailJobsTask');
        }

        $this->_helper->BatchRunner($batch, $this->_('Execute all mail jobs'), $this->accesslog);
    }

    /**
     * The types of mail filters
     *
     * @return array
     */
    protected function getBulkMailFilterOptions()
    {
        return array(
            'N' => $this->_('First mail'),
            'R' => $this->_('Reminder'),
        );
    }

    /**
     * Options for from address use.
     *
     * @return array
     */
    protected function getBulkMailFromOptions()
    {
        $results['O'] = $this->_('Use organizational from address');

        if (isset($project->email['site']) && $project->email['site']) {
            $results['S'] = sprintf($this->_('Use site %s address'), $project->email['site']);
        }

        $results['U'] = $this->_("Use the 'By staff member' address");
        $results['F'] = $this->_('Other');

        return $results;
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

            $request = $this->getRequest();
            if ($menuItem = $this->menu->findController('cron', 'cron-lock')) {
                $menuItem->set('label', $this->_('Turn Automatic Mail Jobs ON'));
            }
        }

        parent::indexAction();

        $this->html->pInfo($this->_('With automatic mail jobs and a cron job on the server, mails can be sent without manual user action.'));
    }

    /**
     * Ajax return function for round selection
     */
    public function roundselectAction()
    {
        \Zend_Layout::resetMvcInstance();
        $trackId = $this->getRequest()->getParam('sourceValue');
        $rounds = $this->db->fetchPairs($this->roundDescriptionQuery, $trackId);
        echo json_encode($rounds);
    }

    public function showAction()
    {
        parent::showAction();

        $id = $this->getRequest()->getParam('id');
        if (!is_null($id)) {
            $id = (int) $id;
            $job = $this->db->fetchRow("SELECT * FROM gems__comm_jobs WHERE gcj_active = 1 and gcj_id_job = ?", $id);
            if ($job) {
                $model  = $this->loader->getTracker()->getTokenModel();
                $filter = $this->loader->getUtil()->getDbLookup()->getFilterForMailJob($job);
                $params['model'] = $model;
                $params['filter'] = $filter;
                $this->addSnippet('TokenPlanTableSnippet', $params);
             }
        }
    }

    public function sortAction()
    {
        $this->_helper->getHelper('SortableTable')->ajaxAction('gems__comm_jobs','gcj_id_job', 'gcj_id_order');        
    }
}