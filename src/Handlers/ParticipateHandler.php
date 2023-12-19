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
use Gems\Menu\RouteHelper;
use Gems\Screens\ScreenRepository;
use Gems\Screens\SubscribeScreenInterface;
use Gems\Screens\UnsubscribeScreenInterface;
use Gems\Site\SiteUtil;
use Gems\User\User;
use Zalt\Base\TranslatorInterface;
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
    use CsrfHandlerTrait;

    /**
     * @var User|null Current user, or null when not logged in.
     */
    protected User|null $currentUser = null;

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

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected CurrentUserRepository $currentUserRepository,
        protected ScreenRepository $screenRepository,
        protected RouteHelper $routeHelper,
        protected ResultFetcher $resultFetcher,
        protected SiteUtil $siteUtil,
        protected array $config,
    ) {
        parent::__construct($responder, $translate);

        \Gems\Html::init();
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Return a list of organizations where the field $onfield has a value.
     */
    protected function _getScreenOrgs($onfield): array
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
     * Ask the user which organization to participate with.
     */
    public function subscribeAction(): void
    {
        $queryParams = $this->request->getQueryParams();
        $orgId = $queryParams['org'] ?? null;

        if ($this->currentUser) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        } else {
            $site = $this->siteUtil->getCurrentSite($this->request);
            $allowedOrganizations = $this->siteUtil->getNamedOrganizationsFromSiteUrl($site);
        }
        $screenOrganizations = $this->_getScreenOrgs('gor_respondent_subscribe');
        $subscribableOrganizations = array_intersect_assoc($allowedOrganizations, $screenOrganizations);

        if ($orgId && !isset($subscribableOrganizations[$orgId])) {
            // The organization Id was set but it is not valid.
            $ordId = null;
        }

        // If there is only one organization we can subscribe to, select it.
        if (count($subscribableOrganizations) == 1) {
            $orgId = key($subscribableOrganizations);
        }

        $this->setCurrentOrganization($orgId);

        // What to show if there are no organizations to subscribe to.
        $params   = [];
        $snippets = ['Subscribe\\NoSubscriptionsSnippet'];

        if ($orgId) {
            $screen = $this->screenRepository->getSubscribeScreenForOrganizationId($orgId);
            if ($screen instanceof SubscribeScreenInterface) {
                $params   = $screen->getSubscribeParameters();
                $snippets = $screen->getSubscribeSnippets();
            }
        } else {
            if ($subscribableOrganizations) {
                $params   = [
                    'action' => 'subscribe',
                    'info'   => $this->_('Select an organization to subscribe to:'),
                    'orgs'   => $subscribableOrganizations,
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            }
        }

        $params = $this->convertLegacyRouteActionToAfterSaveRouteUrl($params);
        $params['csrfName']  = $this->getCsrfTokenName();
        $params['csrfToken'] = $this->getCsrfToken();

        $this->addSnippets('Gems\\Snippets\\Generic\\ContentTitleSnippet', ['contentTitle' => $this->_('Subscribe'), 'tagName' => 'h1']);
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

        if ($this->currentUser) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        } else {
            $site = $this->siteUtil->getCurrentSite($this->request);
            $allowedOrganizations = $this->siteUtil->getNamedOrganizationsFromSiteUrl($site);
        }
        $screenOrganizations = $this->_getScreenOrgs('gor_respondent_unsubscribe');
        $unsubscribableOrganizations = array_intersect_assoc($allowedOrganizations, $screenOrganizations);

        if ($orgId && !isset($unsubscribableOrganizations[$orgId])) {
            // The organization Id was set but it is not valid.
            $ordId = null;
        }

        // If there is only one organization we can subscribe to, select it.
        if (count($unsubscribableOrganizations) == 1) {
            $orgId = key($unsubscribableOrganizations);
        }

        $this->setCurrentOrganization($orgId);

        // What to show if there are no organizations to subscribe to.
        $params   = [];
        $snippets = ['Unsubscribe\\NoUnsubscriptionsSnippet'];

        if ($orgId) {
            $screen = $this->screenRepository->getUnsubscribeScreenForOrganizationId($orgId);
            if ($screen instanceof UnsubscribeScreenInterface) {
                $params   = $screen->getUnsubscribeParameters();
                $snippets = $screen->getUnsubscribeSnippets();
            }
        } else {
            if ($unsubscribableOrganizations) {
                $params   = [
                    'action' => 'unsubscribe',
                    'info'   => $this->_('Select an organization to unsubscribe from:'),
                    'orgs'   => $unsubscribableOrganizations,
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            }
        }

        $params = $this->convertLegacyRouteActionToAfterSaveRouteUrl($params);
        $params['csrfName']  = $this->getCsrfTokenName();
        $params['csrfToken'] = $this->getCsrfToken();

        $this->addSnippets('Gems\\Snippets\\Generic\\ContentTitleSnippet', ['contentTitle' => $this->_('Unsubscribe'), 'tagName' => 'h1']);
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
     * Since we don't have the routeHelper in the Snippets we're loading, convert
     * the route name to a URL here.
     * @param  array  $params Snippet parameters
     * @return array          Updated snippet parameters
     */
    private function convertLegacyRouteActionToAfterSaveRouteUrl(array $params): array
    {
        // Convert routeAction to afterSaveRouteUrl
        if (isset($params['routeAction'])) {
            $params['afterSaveRouteUrl'] = $this->routeHelper->getRouteUrl($params['routeAction']);
            unset($params['routeAction']);
        }
        return $params;
    }

    /**
     * Set the current organization. We set it on the currentUserRepository
     * for the case where we don't have a logged in user, and additionally
     * on the currentUser if we do.
     * @param  int|null $orgId OrganizationId
     * @return void
     */
    private function setCurrentOrganization(?int $orgId): void
    {
        if ($orgId) {
            $this->currentUserRepository->setCurrentOrganizationId($orgId);
            if ($this->currentUser && ($orgId != $this->currentUser->getCurrentOrganizationId())) {
                $this->currentUser->setCurrentOrganization($orgId);
            }
        }
    }
}
