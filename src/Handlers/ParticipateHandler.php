<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 */

namespace Gems\Handlers;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\RouteHelper;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Project\ProjectSettings;
use Gems\Screens\SubscribeScreenInterface;
use Gems\Screens\UnsubscribeScreenInterface;
use Gems\Tracker;
use Gems\User\User;
use Gems\Util\Lock\MaintenanceLock;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\RequestInfoFactory;
use Zalt\SnippetsLoader\SnippetResponderInterface;


/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    No free license, do not copy
 * @since      Class available since version 1.8.6 18-Mar-2019 16:02:12
 */
class ParticipateHandler extends SnippetLegacyHandlerAbstract
{
    protected User|null $currentUser = null;

    /**
     * Snippets displayed when maintenance mode is on
     *
     * @var array
     */
    protected array $maintenanceModeSnippets = [
        MaintenanceModeAskSnippet::class,
    ];


    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * The parameters used for the subscribe-thanks action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $subscribeThanksParameters = [];

    /**
     * The snippets used for the subscribe-thanks action, usually called after unsubscribe
     *
     * @var mixed String or array of snippets name
     */
    protected $subscribeThanksSnippets = ['Subscribe\\ThankYouForSubscribingSnippet'];

    /**
     * The parameters used for the unsubscribe-thanks action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $unsubscribeThanksParameters = [];

    /**
     * The snippets used for the unsubscribe-thanks action, usually called after unsubscribe
     *
     * @var mixed String or array of snippets name
     */
    protected $unsubscribeThanksSnippets = ['Unsubscribe\\UnsubscribedSnippet'];

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
        protected Tracker $tracker,
        CurrentUserRepository $currentUserRepository,
        protected Locale $locale,
        protected ProjectSettings $project,
        protected RouteHelper $routeHelper,
        protected MaintenanceLock $maintenanceLock,
        protected ResultFetcher $resultFetcher,
        protected array $config,
    ) {
        parent::__construct($responder, $translate);

        \Gems\Html::init();
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Return a list of organizations where the field $onfield has a value.
     */
    protected function _getScreenOrgs($onfield)
    {
        $select = $this->resultFetcher->getSelect('gems__organizations')
                ->columns(['gor_id_organization', 'gor_name'])
                ->where("LENGTH($onfield) > 0")
                ->order('gor_name');

        return $this->resultFetcher->fetchPairs($select);
    }

    /**
     * Nothing here yet.
     */
    public function indexAction(): void
    {
    }
   
    /**
     * Ask the user which organization to participate with
     */
    public function subscribeAction(): void
    {
        $queryParams = $this->request->getQueryParams();
        $orgId = $queryParams['org'] ?? null;

        if ($orgId && ($orgId != $this->currentUser->getCurrentOrganizationId())) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
            if ((! $this->currentUser->isActive()) || isset($allowedOrganizations[$orgId])) {
                $this->currentUser->setCurrentOrganization($orgId);
            }
        }

        $screen = $this->currentUser->getCurrentOrganization()->getSubscribeScreen();

        if ($screen instanceof SubscribeScreenInterface) {
            $params   = $screen->getSubscribeParameters();
            $snippets = $screen->getSubscribeSnippets();
        } else {
            $list = $this->_getScreenOrgs('gor_respondent_subscribe');
            if ($list) {
                $params   = [
                    'action' => 'subscribe',
                    'info'   => $this->_('Select an organization to subscribe to:'),
                    'orgs'   => $list,
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            } else {
                $params   = [];
                $snippets = ['Subscribe\\NoSubscriptionsSnippet'];
            }
        }

        $this->html->h1($this->_('Subscribe'));
        $this->addSnippets($snippets, $params);
    }

    /**
     * Show the thanks screen
     */
    public function subscribeThanksAction(): void
    {
        if ($this->subscribeThanksSnippets) {
            $params = $this->_processParameters($this->subscribeThanksParameters);

            $this->addSnippets($this->subscribeThanksSnippets, $params);
        }
    }

    /**
     * Ask the user which organization to unsubscribe from
     */
    public function unsubscribeAction(): void
    {
        $queryParams = $this->request->getQueryParams();
        $orgId = $queryParams['org'] ?? null;

        if ($orgId && $this->currentUser && ($orgId != $this->currentUser->getCurrentOrganizationId())) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
            if ((! $this->currentUser->isActive()) || isset($allowedOrganizations[$orgId])) {
                $this->currentUser->setCurrentOrganization($orgId);
            }
        }

        $screen = $this->currentUser->getCurrentOrganization()->getUnsubscribeScreen();

        if ($screen instanceof UnsubscribeScreenInterface) {
            $params   = $screen->getUnsubscribeParameters();
            $snippets = $screen->getUnsubscribeSnippets();
        } else {
            $list = $this->_getScreenOrgs('gor_respondent_unsubscribe');
            if ($list) {
                $params   = [
                    'action' => 'unsubscribe',
                    'info'   => $this->_('Select an organization to unsubscribe from:'),
                    'orgs'   => $list,
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            } else {
                $params   = [];
                $snippets = ['Unsubscribe\\NoUnsubscriptionsSnippet'];
            }
        }

        $this->html->h1($this->_('Unsubscribe'));
        $this->addSnippets($snippets, $params);
    }

    /**
     * Show the thanks screen
     */
    public function unsubscribeThanksAction(): void
    {
        if ($this->unsubscribeThanksSnippets) {
            $params = $this->_processParameters($this->unsubscribeThanksParameters);

            $this->addSnippets($this->unsubscribeThanksSnippets, $params);
        }
    }

    /**
     * Ask the user which organization to participate with
     */
    public function unsubscribeToOrgAction(): void
    {
        $request = $this->getRequest();
        $orgId   = urldecode($request->getParam('org'));

        $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        if ((! $this->currentUser->isActive()) || isset($allowedOrganizations[$orgId])) {
            $this->currentUser->setCurrentOrganization($orgId);
        }

        $this->forward('unsubscribe');
    }
}
