<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Cache\HelperAdapter;
use Gems\Handlers\SnippetLegacyHandlerAbstract;
use Gems\MenuNew\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Project\ProjectSettings;
use Gems\Util\MaintenanceLock;
use Gems\Versions;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Zalt\Html\Html;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class ProjectInformationHandler  extends SnippetLegacyHandlerAbstract
{
    protected array $_defaultParameters = [];
    protected array $defaultParameters = [];

    protected $monitorParameters = [
        'monitorJob' => 'getMaintenanceMonitorJob'
    ];

    protected $monitorSnippets = 'MonitorSnippet';

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
    public bool $useHtmlView = true;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected UrlHelper $urlHelper,
        protected MaintenanceLock $maintenanceLock,
        protected Versions $versions,
        protected RouteHelper $routeHelper,
        protected ProjectSettings $projectSettings,
        protected HelperAdapter $cache,
    )
    {
        parent::__construct($responder, $translate);
    }

    /**
     * Returns the data to show in the index action
     *
     * Allows to easily add or modifiy the information at project level
     *
     * @return array
     */
    protected function _getData()
    {

        $projectName = null;
        if (isset($this->config['app']['name'])) {
            $projectName = $this->config['app']['name'];
        }

        $data[$this->_('Project name')]            = $projectName;
        $data[$this->_('Project version')]         = $this->versions->getProjectVersion();
        $data[$this->_('Gems version')]            = $this->versions->getGemsVersion();
        $data[$this->_('Gems build')]              = $this->versions->getBuild();
        $data[$this->_('Gems project')]            = $projectName;
        //$data[$this->_('Gems web directory')]      = $this->getDirInfo(GEMS_WEB_DIR);
        //$data[$this->_('Gems root directory')]     = $this->getDirInfo(GEMS_ROOT_DIR);
        //$data[$this->_('Gems code directory')]     = $this->getDirInfo(GEMS_LIBRARY_DIR);
        //$data[$this->_('Gems variable directory')] = $this->getDirInfo(GEMS_ROOT_DIR . '/var');
        $data[$this->_('MUtil version')]           = \MUtil\Version::get();
        $data[$this->_('Application environment')] = getenv('APP_ENV');
        $data[$this->_('Application baseuri')]     = $this->urlHelper->getBasePath();
        //$data[$this->_('Application directory')]   = $this->getDirInfo(APPLICATION_PATH);
        //$data[$this->_('Application encoding')]    = APPLICATION_ENCODING;
        $data[$this->_('PHP version')]             = phpversion();
        $data[$this->_('Server Hostname')]         = php_uname('n');
        $data[$this->_('Server OS')]               = php_uname('s');
        $data[$this->_('Time on server')]          = date('r');

        return $data;
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processParameters(array $input)
    {
        $output = [];

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
        $tableContainer = Html::create()->div(array('class' => 'table-container'));
        $table = \Zalt\Html\TableElement::createArray($data, $caption, $nested);
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

        $params = $this->requestInfo->getRequestMatchedParams();
        if ($emptyLabel && (isset($params[Model::REQUEST_ID]) && 1 == $params[\MUtil\Model::REQUEST_ID]) && file_exists($logFile)) {
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
                $buttons->actionLink(array(\MUtil\Model::REQUEST_ID => 1), $emptyLabel);
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
        if (file_exists(APPLICATION_PATH . '/CHANGELOG.md')) {
            $this->_showText(sprintf($this->_('Changelog %s'), $this->project->getName()), APPLICATION_PATH . '/CHANGELOG.md');
        } else {
            $this->_showText(sprintf($this->_('Changelog %s'), $this->project->getName()), APPLICATION_PATH . '/changelog.txt');
        }
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
        $this->_showText($this->_('Logged errors'), GEMS_ROOT_DIR . '/var/logs/errors.log', $this->_('Empty logfile'));
    }

    public function getMaintenanceMonitorJob()
    {
        return $this->util->getMonitor()->getReverseMaintenanceMonitor();
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
            \MUtil\File::getByteSized($free),
            \MUtil\File::getByteSized($total),
            $percent
        );
    }

    public function indexAction()
    {
        $this->html->h2($this->_('Project information'));

        $data = $this->_getData();

        if ($this->maintenanceLock->isLocked()) {
            $label = $this->_('Turn Maintenance Mode OFF');
        } else {
            $label = $this->_('Turn Maintenance Mode ON');
        }
        /*$request = $this->getRequest();
        $buttonList = $this->menu->getMenuList();
        $buttonList->addParameterSources($request)
            ->addByController($request->getControllerName(), 'maintenance', $label)
            ->addByController($request->getControllerName(), 'monitor')
            ->addByController($request->getControllerName(), 'cacheclean');*/

        $buttonList = [
            \Gems\Html::actionLink($this->routeHelper->getRouteUrl('setup.project-information.cacheclean'), $this->_('Clear cache')),
        ];

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
            \MUtil\EchoOut\EchoOut::out();
        }

        // Redirect
        $this->redirectUrl = $this->urlHelper->generate('setup.project-information.index');
    }

    public function monitorAction() {
        if ($this->monitorSnippets) {
            $params = $this->_processParameters($this->monitorParameters);

            $this->addSnippets($this->monitorSnippets, $params);
        }
    }

    public function cachecleanAction()
    {
        $this->cache->clear();
        /**
         * @var $messenger StatusMessengerInterface
         */
        $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $messenger->addSuccess($this->_('Cache cleaned'));
        // Redirect
        $redirectUrl = $this->urlHelper->generate('setup.project-information.index');
        return new RedirectResponse($redirectUrl);
    }

    public function phpAction()
    {
        $this->html->h2($this->_('Server PHP Info'));

        $php = new \MUtil\Config\Php();

        $this->html->raw($php->getInfo());
    }

    public function phpErrorsAction()
    {
        $this->_showText($this->_('Logged PHP errors'), ini_get('error_log'), $this->_('Empty PHP error file'));
    }

    public function projectAction()
    {
        //Clone the object, we don't want to modify the original
        $project = clone $this->projectSettings;

        //Now remove some keys want to keep for ourselves
        if ($project->offsetExists('admin')) {
            $project->offsetUnset('admin');
        }
        if ($project->offsetExists('salt')) {
            $project->offsetUnset('salt');
        }
        if ($project->offsetExists('dependencies')) {
            $project->offsetUnset('dependencies');
        }
        if ($project->offsetExists('routes')) {
            $project->offsetUnset('routes');
        }

        $this->html->h2($this->_('Project settings'));
        $this->_showTable(GEMS_PROJECT_NAME . ' Project.ini', $project);
    }


    public function sessionAction()
    {
        $this->html->h2($this->_('Session content'));
        $this->_showTable($this->_('Session'), $_SESSION);
    }
}