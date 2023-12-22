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

use Gems\Audit\AuditLog;
use Gems\Cache\HelperAdapter;
use Gems\Handlers\SnippetLegacyHandlerAbstract;
use Gems\Html;
use Gems\Log\ErrorLogger;
use Gems\Log\Loggers;
use Gems\Menu\RouteHelper;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Project\ProjectSettings;
use Gems\Snippets\MonitorSnippet;
use Gems\Util\Lock\MaintenanceLock;
use Gems\Util\Monitor\Monitor;
use Gems\Versions;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\UrlArrayAttribute;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\MetaModelInterface;
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
    protected $monitorParameters = [
        'currentMonitor' => MonitorSnippet::MAINTENANCE,
    ];

    protected $monitorSnippets = 'MonitorSnippet';

    protected array $reportPackageVersions = [
        'magnafacta/mutil',
        'magnafacta/zalt-html',
        'magnafacta/zalt-laminas-filter',
        'magnafacta/zalt-laminas-validator',
        'magnafacta/zalt-late',
        'magnafacta/zalt-loader',
        'magnafacta/zalt-model',
        'magnafacta/zalt-soap',
        'magnafacta/zalt-util',
        'gemstracker/gems-api',
        'gemstracker/gems-fhir-api',
        'gemstracker/gems-oauth2',
        'gemstracker/gemstracker',
    ];

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
        protected AuditLog $auditLog,
        protected HelperAdapter $cache,
        protected Loggers $loggers,
        protected MaintenanceLock $maintenanceLock,
        protected readonly Monitor $monitor,
        protected ProjectSettings $projectSettings,
        protected RouteHelper $routeHelper,
        protected UrlHelper $urlHelper,
        protected Versions $versions,
        protected readonly array $config,
    )
    {
        parent::__construct($responder, $translate);

        Html::init();
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
        $data[$this->_('Gems web directory')]      = $this->projectSettings->publicDir;
        $data[$this->_('Gems root directory')]     = $this->projectSettings->rootDir;
        //$data[$this->_('Gems code directory')]     = $this->getDirInfo(GEMS_LIBRARY_DIR);
        //$data[$this->_('Gems variable directory')] = $this->getDirInfo(GEMS_ROOT_DIR . '/var');
        $data[$this->_('MUtil version')]           = \MUtil\Version::get();
        $data[$this->_('Application environment')] = $_ENV['APP_ENV'] ?? null;
        $data[$this->_('Application baseuri')]     = $this->urlHelper->getBasePath();
        //$data[$this->_('Application directory')]   = $this->getDirInfo(APPLICATION_PATH);
        //$data[$this->_('Application encoding')]    = APPLICATION_ENCODING;
        $data[$this->_('PHP version')]             = phpversion();
        $data[$this->_('Server Hostname')]         = php_uname('n');
        $data[$this->_('Server OS')]               = php_uname('s');
        $data[$this->_('Time on server')]          = date('r');
        foreach ($this->reportPackageVersions as $package) {
            if (! \Composer\InstalledVersions::isInstalled($package)) {
                continue;
            }
            $display = sprintf('%s (%s)',
                \Composer\InstalledVersions::getPrettyVersion($package),
                \Composer\InstalledVersions::getReference($package));
                $data[$this->_('Version').' '.$package] = $display;
        }

        return $data;
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

        dump($this->requestInfo);
        $param = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID);
        if ($emptyLabel && (1 == $param) && file_exists($logFile)) {
            file_put_contents($logFile, '');
//            unlink($logFile);
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
                $buttons->actionLink(UrlArrayAttribute::toUrlString([$this->requestInfo->getBasePath(), MetaModelInterface::REQUEST_ID => 1]), $emptyLabel);
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
        if (file_exists($this->config['rootDir'] . '/CHANGELOG.md')) {
            $this->_showText(sprintf($this->_('Changelog %s'), $this->projectSettings->getName()), $this->config['rootDir'] . '/CHANGELOG.md');
        } else {
            $this->_showText(sprintf($this->_('Changelog %s'), $this->projectSettings->getName()), $this->config['rootDir'] . '/changelog.txt');
        }
    }

    /**
     * Show the GemsTracker change log
     */
    public function changelogGemsAction()
    {
        $this->_showText(sprintf($this->_('Changelog %s'), 'GemsTracker'), $this->config['rootDir'] . 'vendor/gemstracker/gemstracker/CHANGELOG.md', null, 'GemsTracker/gemstracker-library');
    }

    protected function getLogFile(string $loggerName): ?string
    {
        $logger = $this->loggers->getLogger($loggerName);

        if ($logger instanceof Logger) {
            $handlers = $logger->getHandlers();
            foreach($handlers as $handler) {
                if ($handler instanceof StreamHandler) {
                    $url = $handler->getUrl();
                    if ($url !== null && is_readable($url)) {
                        return $url;
                    }
                }
            }
        }
        return null;
    }

    public function errorsAction()
    {
        $logFile = $this->getLogFile('LegacyLogger');

        if ($logFile !== null) {
            $this->_showText(
                $this->_('Logged errors'),
                $logFile,
                $this->_('Empty logfile')
            );
            return;
        }
        $this->html->div()->append($this->_('No log file set for output'));
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
            \Zalt\File\File::getByteSized((int)$free),
            \Zalt\File\File::getByteSized((int)$total),
            $percent
        );
    }

    public function indexAction()
    {
        $this->html->h2($this->_('Project information'));

        $data = $this->_getData();

        if ($this->maintenanceLock->isLocked()) {
            $maintenanceLockLabel = $this->_('Turn Maintenance Mode OFF');
        } else {
            $maintenanceLockLabel = $this->_('Turn Maintenance Mode ON');
        }
        /*$request = $this->getRequest();
        $buttonList = $this->menu->getMenuList();
        $buttonList->addParameterSources($request)
            ->addByController($request->getControllerName(), 'maintenance', $label)
            ->addByController($request->getControllerName(), 'monitor')
            ->addByController($request->getControllerName(), 'cacheclean');*/

        $buttonList = [
            \Gems\Html::actionLink($this->routeHelper->getRouteUrl('setup.project-information.maintenance-mode'), $maintenanceLockLabel),
            \Gems\Html::actionLink($this->routeHelper->getRouteUrl('setup.project-information.cacheclean'), $this->_('Clear cache')),
        ];

        // $this->html->buttonDiv($buttonList);

        $this->_showTable($this->_('Version information'), $data);

        $this->html->buttonDiv($buttonList);
    }

    /**
     * Action that switches the maintenance lock on or off.
     */
    public function maintenanceModeAction()
    {
        /**
         * @var StatusMessengerInterface $messenger
         */
        $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $messenger->clearMessages();
        if ($this->monitor->reverseMaintenanceMonitor()) {
            $messenger->addSuccess($this->_('Maintenance mode set ON'));
        } else {
            $messenger->addSuccess($this->_('Maintenance mode set OFF'));
        }

        // Redirect
        return new RedirectResponse($this->routeHelper->getRouteUrl('setup.project-information.index'));
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
         * @var StatusMessengerInterface $messenger
         */
        $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $messenger->addSuccess($this->_('Cache cleaned'));

         $this->auditLog->registerChanges(['cache' => 'cleaned'], logId:  $this->auditLog->getLastLogId());

        // Redirect
        $redirectUrl = $this->urlHelper->generate('setup.project-information.index');
        return new RedirectResponse($redirectUrl);
    }

    public function phpAction()
    {
        $this->html->h2($this->_('Server PHP Info'));

	// Don't show environment or variables, these contain sensitive information.
        $php = new \MUtil\Config\Php(INFO_GENERAL|INFO_CREDITS|INFO_CONFIGURATION|INFO_MODULES|INFO_LICENSE);

        $this->html->raw($php->getInfo());
    }

    public function phpErrorsAction()
    {
        $logFile = $this->getLogFile(ErrorLogger::class);

        if ($logFile !== null) {
            $this->_showText(
                $this->_('Logged errors'),
                $logFile,
                $this->_('Empty PHP error file')
            );
            return;
        }
        $this->html->div()->append($this->_('No log file set for output'));
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
        // Don't show the database password in plain text.
        if ($project->offsetExists('db')) {
            if (isset($project->db['password'])) {
                $project->db['password'] = '********';
            }
        }

        $this->html->h2($this->_('Project settings'));
        $appName = $this->config['app']['name'] ?? 'GemsTracker';
        $this->_showTable($appName . ' Project.ini', $project);
    }


    public function sessionAction()
    {
        $this->html->h2($this->_('Session content'));
        $session = $this->request->getAttribute(SessionInterface::class);
        if ($session) {
            // Mezzio session.
            $sessionData = $session->toArray();
        } elseif (isset($_SESSION)) {
            // PHP session.
            $sessionData = $_SESSION;
        } else {
            $sessionData = [];
        }
        $this->_showTable($this->_('Session'), $sessionData);
    }
}
