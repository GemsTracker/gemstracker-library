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
use Gems\Repository\OrganizationRepository;
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
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly RouteHelper $routeHelper,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly ScreenRepository $screenRepository,
        protected readonly SiteUtil $siteUtil,
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

    public function getOrganizationId(array &$subscribableOrganizations): ?int
    {
        $orgId = $this->requestInfo->getParam('org');
        if (! is_numeric($orgId)) {
            $orgs  = $this->organizationRepository->getOrganizationsByCode($orgId);
            $orgId = array_key_first($orgs);
        }

        if ($this->currentUser) {
            $allowedOrganizations = $this->currentUser->getAllowedOrganizations();
        } else {
            $site = $this->siteUtil->getCurrentSite($this->request);
            $allowedOrganizations = $this->siteUtil->getNamedOrganizationsFromSiteUrl($site);
        }
        $subscribableOrganizations = array_intersect_assoc($allowedOrganizations, $subscribableOrganizations);

        if ($orgId && !isset($subscribableOrganizations[$orgId])) {
            // The organization Id was set but it is not valid.
            $orgId = null;
        }

        if (count($subscribableOrganizations) == 1) {
            $orgId = array_key_first($subscribableOrganizations);
        }

        if ($orgId) {
            $this->setCurrentOrganization($orgId);
        }

        return $orgId;
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
        $subscribableOrganizations = $this->_getScreenOrgs('gor_respondent_subscribe');
        $orgId = $this->getOrganizationId($subscribableOrganizations);

        // What to show if there are no organizations to subscribe to.
        $params   = [];
        $snippets = ['Subscribe\\NoSubscriptionsSnippet'];

        if ($orgId) {
            $params['afterSaveRouteUrl'] = $this->routeHelper->getRouteUrl('participate.subscribe-thanks');

            $screen = $this->screenRepository->getSubscribeScreenForOrganizationId($orgId);
            if ($screen instanceof SubscribeScreenInterface) {
                $params   = $screen->getSubscribeParameters() + $params;
                $snippets = $screen->getSubscribeSnippets();
            }
        } else {
            if ($subscribableOrganizations) {
                $params   = [
                    'info'   => $this->_('Select an organization to subscribe to:'),
                    'orgs'   => $subscribableOrganizations,
                    'route'  => 'participate.subscribe-form',
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            }
        }

        $params['csrfName']  = $this->getCsrfTokenName();
        $params['csrfToken'] = $this->getCsrfToken();

        $this->addSnippets('Gems\\Snippets\\Generic\\ContentTitleSnippet', ['contentTitle' => $this->_('Subscribe'), 'tagName' => 'h1']);
        $this->addSnippets($snippets, $params);
    }

    public function subscribeFormAction(): void
    {
        $this->subscribeAction();
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
        $unsubscribableOrganizations = $this->_getScreenOrgs('gor_respondent_unsubscribe');
        $orgId = $this->getOrganizationId($unsubscribableOrganizations);

        // What to show if there are no organizations to subscribe to.
        $params   = [];
        $snippets = ['Unsubscribe\\NoUnsubscriptionsSnippet'];

        if ($orgId) {
            $params['afterSaveRouteUrl'] = $this->routeHelper->getRouteUrl('participate.unsubscribe-thanks');

            $screen = $this->screenRepository->getUnsubscribeScreenForOrganizationId($orgId);
            if ($screen instanceof UnsubscribeScreenInterface) {
                $params   = $screen->getUnsubscribeParameters() + $params;
                $snippets = $screen->getUnsubscribeSnippets();
            }
        } else {
            if ($unsubscribableOrganizations) {
                $params   = [
                    'info'   => $this->_('Select an organization to unsubscribe from:'),
                    'orgs'   => $unsubscribableOrganizations,
                    'route'  => 'participate.unsubscribe-form',
                    ];
                $snippets = ['Organization\\ChooseListedOrganizationSnippet'];
            }
        }

        $params['csrfName']  = $this->getCsrfTokenName();
        $params['csrfToken'] = $this->getCsrfToken();

        $this->addSnippets('Gems\\Snippets\\Generic\\ContentTitleSnippet', ['contentTitle' => $this->_('Unsubscribe'), 'tagName' => 'h1']);
        $this->addSnippets($snippets, $params);
    }

    public function unsubscribeFormAction(): void
    {
        $this->unsubscribeAction();
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
                $this->currentUser->setCurrentOrganizationId($orgId);
            }
        }
    }
}
