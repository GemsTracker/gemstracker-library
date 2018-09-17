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
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ProjectInformationAction  extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    protected $_defaultParameters = array();
    protected $defaultParameters = array();

    /**
     *
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    protected $monitorParameters = array(
        'monitorJob' => 'getMaintenanceMonitorJob'
    );

    protected $monitorSnippets = 'MonitorSnippet';

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * To initiate the use of $this->html from the code call $this->initHtml()
     *
     * Overrules $useRawOutput.
     *
     * @see $useRawOutput
     * @var boolean $useHtmlView
     */
    public $useHtmlView = true;

    /**
     * Returns the data to show in the index action
     *
     * Allows to easily add or modifiy the information at project level
     *
     * @return array
     */
    protected function _getData()
    {
        $versions = $this->loader->getVersions();

        $data[$this->_('Project name')]            = $this->project->getName();
        $data[$this->_('Project version')]         = $versions->getProjectVersion();
        $data[$this->_('Gems version')]            = $versions->getGemsVersion();
        $data[$this->_('Gems build')]              = $versions->getBuild();
        $data[$this->_('Gems project')]            = GEMS_PROJECT_NAME;
        $data[$this->_('Gems web directory')]      = $this->getDirInfo(GEMS_WEB_DIR);
        $data[$this->_('Gems root directory')]     = $this->getDirInfo(GEMS_ROOT_DIR);
        $data[$this->_('Gems code directory')]     = $this->getDirInfo(GEMS_LIBRARY_DIR);
        $data[$this->_('Gems variable directory')] = $this->getDirInfo(GEMS_ROOT_DIR . '/var');
        $data[$this->_('MUtil version')]           = \MUtil_Version::get();
        $data[$this->_('Zend version')]            = \Zend_Version::VERSION;
        $data[$this->_('Application environment')] = APPLICATION_ENV;
        $data[$this->_('Application baseuri')]     = $this->loader->getUtil()->getCurrentURI();
        $data[$this->_('Application directory')]   = $this->getDirInfo(APPLICATION_PATH);
        $data[$this->_('Application encoding')]    = APPLICATION_ENCODING;
        $data[$this->_('PHP version')]             = phpversion();
        $data[$this->_('Server Hostname')]         = php_uname('n');
        $data[$this->_('Server OS')]               = php_uname('s');
        $data[$this->_('Time on server')]          = date('r');

        $driveVars = array(
            $this->_('Session directory') => \Zend_Session::getOptions('save_path'),
            $this->_('Temporary files directory') => realpath(getenv('TMP')),
        );
        if ($system =  getenv('SystemDrive')) {
            $driveVars[$this->_('System Drive')] = realpath($system);
        }
        foreach ($driveVars as $name => $drive) {
            $data[$name] = $this->getDirInfo($drive);
        }

        return $data;
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input)
    {
        $output = array();

        foreach ($input + $this->defaultParameters + $this->_defaultParameters as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value($key);

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }

        return $output;
    }

    protected function _showTable($caption, $data, $nested = false)
    {
        $tableContainer = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $table = \MUtil_Html_TableElement::createArray($data, $caption, $nested);
        $table->class = 'browser table';
        $tableContainer[] = $table;
        $this->html[] = $tableContainer;
    }

    /**
     * Helper function to show content of a text file
     *
     * @param string $caption
     * @param string $logFile
     * @param string $emptyLabel
     */
    protected function _showText($caption, $logFile, $emptyLabel = null, $context = null)
    {
        $this->html->h2($caption);

        if ($emptyLabel && (1 == $this->_getParam(\MUtil_Model::REQUEST_ID)) && file_exists($logFile)) {
            unlink($logFile);
        }

        if (file_exists($logFile)) {
            if (is_readable($logFile)) {
                $content = trim(file_get_contents($logFile));

                if ($content) {
                    $error = false;
                } else {
                    $error = $this->_('empty file');
                }
            } else {
                $error = $this->_('file content not readable');
            }
        } else {
            $content = null;
            $error   = $this->_('file not found');
        }

        if ($emptyLabel) {
            $buttons = $this->html->buttonDiv();
            if ($error) {
                $buttons->actionDisabled($emptyLabel);
            } else {
                $buttons->actionLink(array(\MUtil_Model::REQUEST_ID => 1), $emptyLabel);
            }
        }

        if ($error) {
            $this->html->pre($error, array('class' => 'disabled logFile'));
        } elseif (substr($logFile, -3) == '.md') {            
            $parseDown = new \Gems\Parsedown($context);
            $this->html->div(array('class'=>'logFile'))->raw($parseDown->parse($content));
        } else {
            $this->html->pre($content, array('class' => 'logFile'));
        }

        if ($emptyLabel) {
            // Buttons at both bottom and top.
            $this->html[] = $buttons;
        }
    }

    /**
     * Show the project specific change log
     */
    public function changelogAction()
    {
        $this->_showText(sprintf($this->_('Changelog %s'), $this->escort->project->name), APPLICATION_PATH . '/changelog.txt');
    }

    /**
     * Show the GemsTracker change log
     */
    public function changelogGemsAction()
    {
        $this->_showText(sprintf($this->_('Changelog %s'), 'GemsTracker'), GEMS_LIBRARY_DIR . '/CHANGELOG.md', null, 'GemsTracker/gemstracker-library');
    }

    public function errorsAction()
    {
        $this->logger->shutdown();

        $this->_showText($this->_('Logged errors'), GEMS_ROOT_DIR . '/var/logs/errors.log', $this->_('Empty logfile'));
    }

    public function getMaintenanceMonitorJob()
    {
        return $this->loader->getUtil()->getMonitor()->getReverseMaintenanceMonitor();
    }

    /**
     * Tell all about it
     *
     * @param string $directory
     * @return string
     */
    protected function getDirInfo($directory)
    {
        if (! is_dir($directory)) {
            return sprintf($this->_('%s - does not exist'), $directory);
        }

        $free = disk_free_space($directory);
        $total = disk_total_space($directory);

        if ((false === $free) || (false === $total)) {
            return sprintf($this->_('%s - no disk information available'), $directory);
        }

        $percent = intval($free / $total * 100);

        return sprintf(
                $this->_('%s - %s free of %s = %d%% available'),
                $directory,
                \MUtil_File::getByteSized($free),
                \MUtil_File::getByteSized($total),
                $percent
                );
    }

    public function indexAction()
    {
        $this->html->h2($this->_('Project information'));

        $data = $this->_getData();

        $lock = $this->util->getMaintenanceLock();
        if ($lock->isLocked()) {
            $label = $this->_('Turn Maintenance Mode OFF');
        } else {
            $label = $this->_('Turn Maintenance Mode ON');
        }
        $request = $this->getRequest();
        $buttonList = $this->menu->getMenuList();
        $buttonList->addParameterSources($request)
                ->addByController($request->getControllerName(), 'maintenance', $label)
                ->addByController($request->getControllerName(), 'monitor')
                ->addByController($request->getControllerName(), 'cacheclean');

        // $this->html->buttonDiv($buttonList);

        $this->_showTable($this->_('Version information'), $data);

        $this->html->buttonDiv($buttonList);
    }

    /**
     * Action that switches the maintenance lock on or off.
     */
    public function maintenanceAction()
    {
        // Switch lock
        if ($this->util->getMonitor()->reverseMaintenanceMonitor()) {
            $this->accesslog->logChange($this->getRequest(), $this->_('Maintenance mode set ON'));
        } else {
            $this->accesslog->logChange($this->getRequest(), $this->_('Maintenance mode set OFF'));

            // Dump the existing maintenance mode messages.
            $this->escort->getMessenger()->clearCurrentMessages();
            $this->escort->getMessenger()->clearMessages();
            \MUtil_Echo::out();
        }

        // Redirect
        $request = $this->getRequest();
        $this->_reroute(array($request->getActionKey() => 'index'));
    }

    public function monitorAction() {
        if ($this->monitorSnippets) {
            $params = $this->_processParameters($this->monitorParameters);

            $this->addSnippets($this->monitorSnippets, $params);
        }
    }

    public function cachecleanAction()
    {
        $this->escort->cache->clean();
        $this->addMessage($this->_('Cache cleaned'));
        // Redirect
        $request = $this->getRequest();
        $this->_reroute(array($request->getActionKey() => 'index'));
    }

    public function phpAction()
    {
        $this->html->h2($this->_('Server PHP Info'));

        $php = new \MUtil_Config_Php();

        $this->view->headStyle($php->getStyle());
        $this->html->raw($php->getInfo());
    }

    public function phpErrorsAction()
    {
        $this->_showText($this->_('Logged PHP errors'), ini_get('error_log'), $this->_('Empty PHP error file'));
    }

    public function projectAction()
    {
        //Clone the object, we don't want to modify the original
        $project = clone $this->escort->project;

        //Now remove some keys want want to keep for ourselves
        unset($project['admin']);
        unset($project['salt']);

        $this->html->h2($this->_('Project settings'));
        $this->_showTable(GEMS_PROJECT_NAME . 'Project.ini', $project);
    }


    public function sessionAction()
    {
        $this->html->h2($this->_('Session content'));
        $this->_showTable($this->_('Session'), $this->session);
    }
}