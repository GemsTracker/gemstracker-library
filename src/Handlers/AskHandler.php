<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers;

use Gems\CookieResponse;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\RouteHelper;
use Gems\Menu\RouteNotFoundException;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Project\ProjectSettings;
use Gems\Screens\AskScreenInterface;
use Gems\Snippets\Ask\MaintenanceModeAskSnippet;
use Gems\Snippets\Ask\ResumeLaterSnippet;
use Gems\Snippets\Ask\ShowAllOpenSnippet;
use Gems\Snippets\Token\TokenForgottenSnippet;
use Gems\Tracker;
use Gems\Tracker\Form\AskTokenForm;
use Gems\Tracker\Source\SurveyNotFoundException;
use Gems\Tracker\Token;
use Gems\Tracker\Token\TokenHelpers;
use Gems\User\User;
use Gems\Util\Lock\MaintenanceLock;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\RequestInfoFactory;
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
class AskHandler extends SnippetLegacyHandlerAbstract
{
    use CookieHandlerTrait;
    use CsrfHandlerTrait;

    protected User|null $currentUser = null;

    protected string|null $changeLocaleTo = null;

    /**
     * Usually a child of \Gems\Tracker\Snippets\ShowTokenLoopAbstract,
     * Ask_ShowAllOpenSnippet or Ask_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array of snippet names, presumably \Gems\Tracker\Snippets\ShowTokenLoopAbstract snippets
     */
    // protected $forwardSnippets = 'Ask\\ShowAllOpenSnippet';
    // protected $forwardSnippets = 'Ask\\RedirectUntilGoodbyeSnippet';
    protected array $forwardSnippets = [
        ShowAllOpenSnippet::class
    ];

    /**
     * The width factor for the label elements.
     *
     * Width = (max(characters in labels) * labelWidthFactor) . 'em'
     */
    protected float $labelWidthFactor = 0.8;

    /**
     * The parameters used for the lost action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $lostParameters = [
        'clientIp'  => 'getClientIpAddress',
    ];

    /**
     * The snippets used for the lost action.
     *
     * @var array of snippets name
     */
    protected array $lostSnippets = [
        TokenForgottenSnippet::class
    ];

    /**
     * Snippets displayed when maintenance mode is on
     *
     * @var array
     */
    protected array $maintenanceModeSnippets = [
        MaintenanceModeAskSnippet::class,
    ];

    /**
     * The parameters used for the lost action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $resumeLaterParameters = [];

    /**
     * Usually a child of \Gems\Tracker\Snippets\ShowTokenLoopAbstract,
     * Ask_ShowAllOpenSnippet or Ask_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array Or string of snippet names, presumably \Gems\Tracker\Snippets\ShowTokenLoopAbstract snippets
     */
    protected array $resumeLaterSnippets = [
        ResumeLaterSnippet::class
    ];

    protected ?string $tokenId = null;

    /**
     * The current token
     *
     * set by _initToken()
     */
    protected ?Token $token = null;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected readonly Tracker $tracker,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly Locale $locale,
        protected ProjectSettings $project,
        protected readonly RouteHelper $routeHelper,
        protected readonly MaintenanceLock $maintenanceLock,
        protected readonly TokenHelpers $tokenHelpers,
        protected readonly array $config,
    ) {
        parent::__construct($responder, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();

    }

    /**
     * Common handler utility to initialize tokens from parameters
     *
     * @return boolean True if there is a real token specified in the request
     */
    protected function _initToken(): bool
    {
        if ($this->tracker && $this->token instanceof Token) {
            return $this->token && $this->token->exists;
        }

        $tokenId = $this->request->getAttribute(MetaModelInterface::REQUEST_ID);
        if (null === $tokenId && $this->requestInfo->isPost()) {
            $postData = $this->request->getParsedBody();
            if (isset($postData[MetaModelInterface::REQUEST_ID])) {
                $tokenId = $postData[MetaModelInterface::REQUEST_ID];
            }
        }

        if (null === $tokenId) {
            return false;
        }

        $this->tokenId = $this->tracker->filterToken($tokenId);
        // Now check if the token is valid
        $validator = $this->tracker->getTokenValidator($this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE));

        if (! $this->tokenId || $validator->isValid($this->tokenId) === false) {
            return false;
        }

        $this->token = $this->tracker->getToken($this->tokenId);

        if (! $this->token->exists) {
            return false;
        }

        $tokenOrganizationId = $this->token->getOrganizationId();
        $tokenLang = strtolower($this->token->getRespondentLanguage());
        if (($this->currentUser instanceof User && $this->currentUser->isActive())) {
            if ($tokenOrganizationId !== $this->currentUser->getCurrentOrganizationId()) {
                $this->currentUser->setCurrentOrganizationId($this->token->getOrganizationId());
                $this->currentUserRepository->setCurrentOrganizationId($tokenOrganizationId);
            }
            if ($tokenLang != $this->locale->getLanguage()) {
                $this->changeLocaleTo = $tokenLang;
                $this->locale->setCurrentLanguage($tokenLang);
            }
        } else {
            if ($tokenOrganizationId != $this->currentUserRepository->getCurrentOrganizationId()) {
                $this->addSiteCookie(CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE, (string) $tokenOrganizationId);
                $this->currentUserRepository->setCurrentOrganizationId($tokenOrganizationId);
            }
            if ($tokenLang != $this->locale->getLanguage()) {
                $this->addSiteCookie(LocaleMiddleware::LOCALE_ATTRIBUTE, $tokenLang);
                $this->locale->setCurrentLanguage($tokenLang);
            }
        }

        return true;
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processAskParameters(array $input): array
    {
        $output = [];

        foreach ($input as $key => $value) {
            if (is_string($value) && method_exists($this, $value)) {
                $value = $this->$value($key);

                if (is_integer($key) || ($value === null)) {
                    continue;
                }
            }
            $output[$key] = $value;
        }
        if (! isset($output['token'])) {
            if (! $this->token) {
                $this->_initToken();
            }
            if ($this->token) {
                $output['token'] = $this->token;
            }
        }

        return $output;
    }

    protected function checkReturnUrl(string $url): string
    {
        // Fix for ask index redirect to forward not set in HTTP_REFERER in RedirectLoop
        $urlWithoutQueryParams = strstr($url, '?', true);
        $askIndexUrl = $this->routeHelper->getRouteUrl('ask.index');

        if ($this->token instanceof Token && ((! $url) || str_ends_with($urlWithoutQueryParams, $askIndexUrl))) {
            $forwardUrl = $this->routeHelper->getRouteUrl('ask.forward', [
                MetaModelInterface::REQUEST_ID => $this->token->getTokenId(),
            ]);
            // Add the supplied query params
            //$forwardUrl .= strstr($url, '?');
            return $forwardUrl;
        }

        return $url;
    }

    /**
     * Function for overruling the display of the login form.
     *
     * @param AskTokenForm $form
     */
    protected function displayTokenForm(AskTokenForm $form)
    {
        $form->setDescription(sprintf($this->_('Enter your %s token'), $this->project->getName()));
        $this->html->h3($form->getDescription());
        $this->html[] = $form;
        $this->html->pInfo(
            $this->_('Tokens identify a survey that was assigned to you personally.'), ' ',
            $this->_('Entering the token and pressing OK will open that survey.'), ' '
        );

        if ($this->currentUser !== null && $this->currentUser->isActive()) {
            if ($this->currentUser->isLogoutOnSurvey()) {
                $this->html->pInfo($this->_('After answering the survey you will be logged off automatically.'), ' ');
            }
        }

        $this->html->pInfo(
            $this->_('A token consists of two groups of four letters and numbers, separated by an optional hyphen. Tokens are case insensitive.'), ' ',
            $this->_('The number zero and the letter O are treated as the same; the same goes for the number one and the letter L.')
        );

        $p = $this->html->p();

        try {
            $lostUrl = $this->getActionUrl('lost');
            $p->append(\Gems\Html::actionLink($lostUrl, $this->_('Token lost?')), ' ');
        } catch(RouteNotFoundException $exception) {
        }
    }

    protected function forward(string $action)
    {
        return $this->getRedirectResponse($this->getActionUrl($action));
    }

    /**
     * Show the user a screen with token information and a button to take at least one survey
     *
     * @return mixed
     */
    public function forwardAction()
    {
        /**************
         * Find token *
         **************/

        $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        if (! $this->_initToken()) {
            if ($this->tokenId) {
                // There is a token but is incorrect
                $messenger->addMessage(sprintf(
                    $this->_('The token %s does not exist (any more).'),
                    strtoupper($this->tokenId)
                ));
            }
            return $this->forward('index');
        }

        if ($this->maintenanceLock->isLocked()) {
            $this->addSnippets($this->maintenanceModeSnippets, ['token' => $this->token]);
            return;
        }

        /****************************
         * Update open tokens first *
         ****************************/

        $session = $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        $this->tracker->processCompletedTokens(
            $session,
            $this->token->getRespondentId(),
            $this->token->getChangedBy(),
            $this->token->getOrganizationId()
        );

        $screen = $this->token->getOrganization()->getTokenAskScreen();
        $params = [];
        $forwardSnippets = $this->forwardSnippets;
        if ($screen instanceof AskScreenInterface) {
            $params = $screen->getParameters($this->token);
            if (false !== $screen->getSnippets($this->token)) {
                $forwardSnippets = $screen->getSnippets($this->token);
            }
        }

        $params['token'] = $this->token;
        $params['clientIp'] = $this->getClientIpAddress();

        $params['requestInfo'] = $this->getRequestInfo();

        // Display token when possible
        if ($this->html->snippet($forwardSnippets, $params)) {
            return;
        }

        // Snippet had nothing to display, because of an answer
        if ($this->requestInfo->getCurrentAction() == 'return') {
            $messenger->addMessage(sprintf(
                $this->_('Thank you for answering. At the moment we have no further surveys for you to take.'),
                strtoupper($this->tokenId)
            ));
        } else {
            $messenger->addMessage(sprintf(
                $this->_('The survey for token %s has been answered and no further surveys are open.'),
                strtoupper($this->tokenId)
            ));
        }

        $url = $this->routeHelper->getRouteUrl('ask.index');

        // Do not enter a loop!! Reroute!
        return $this->getRedirectResponse($url);
    }

    protected function getActionUrl(string $action): string
    {

        $route = $this->routeHelper->getRouteSibling($this->requestInfo->getRouteName(), $action);
        $newRouteParams = $this->routeHelper->getRouteParamsFromKnownParams($route, $this->requestInfo->getRequestMatchedParams());

        return $this->routeHelper->getRouteUrl($route['name'], $newRouteParams);
    }

    public function getClientIpAddress(): ?string
    {
        return $this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE);
    }

    public function getRequestInfo(): RequestInfo
    {
        return RequestInfoFactory::getMezzioRequestInfo($this->request);
    }

    protected function getRedirectResponse(string $url): RedirectResponse
    {
        $response = new RedirectResponse($url);
        if ($this->changeLocaleTo) {
            /**
             * @var RedirectResponse $response
             */
            $response = CookieResponse::addCookieToResponse($this->request, $response, LocaleMiddleware::LOCALE_ATTRIBUTE, $this->changeLocaleTo);
        }
        return $response;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return $this->processResponseCookies(parent::handle($request));
    }

    /**
     * Ask the user for a token
     *
     * @return void
     */
    public function indexAction()
    {
        if ($this->maintenanceLock->isLocked()) {
            $this->addSnippets($this->maintenanceModeSnippets);
            return;
        }

        $form = $this->tracker->getAskTokenForm([
            'displayOrder' => [
                'element',
                'description',
                'errors',
            ],
            'labelWidthFactor' => 0.8,
            'clientIp' => $this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE),
        ]);

        if ($this->requestInfo->isPost() && $form->isValid($this->requestInfo->getRequestPostParams(), false)) {
            $params = $this->requestInfo->getRequestPostParams();
            if (isset($params['id'])) {
                $tokenId = $this->tracker->filterToken($params['id']);
                if (!empty($tokenId)) {
                    $route = $this->routeHelper->getRouteSibling($this->requestInfo->getRouteName(), 'forward');
                    $routeUrl = $this->routeHelper->getRouteUrl($route['name'], [
                        MetaModelInterface::REQUEST_ID => $tokenId,
                    ]);
                    return $this->getRedirectResponse($routeUrl);
                }
            }
        }

        $form->populate($this->request->getParsedBody());
        $this->displayTokenForm($form);
    }

    public function logout(): void
    {
        /** @var SessionInterface $session */
        $session = $this->request->getAttribute(SessionInterface::class);
        $session->regenerate();
        $session->clear();
    }

    /**
     * Show lost token screen for respondents
     */
    public function lostAction()
    {
        $this->addSnippets($this->lostSnippets, $this->_processAskParameters($this->lostParameters));
    }

    /**
     * If the user signalled to resume later
     */
    public function resumeLaterAction()
    {
        $this->addSnippets($this->resumeLaterSnippets, $this->_processAskParameters($this->resumeLaterParameters));
    }

    /**
     * The action where survey sources should return to after survey completion
     */
    public function returnAction()
    {
        if (! $this->_initToken()) {
            // In all other cases: the action that generates meaningfull warnings and is reachable for everyone
            return $this->forward('forward');
        }

        if ((! ($this->currentUser instanceof User && $this->currentUser->isActive())) && $this->requestInfo->getParam('resumeLater', 0)) {
            return $this->forward('resume-later');
        }

        $session = $this->request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if ($url = $this->token->getReturnUrl()) {
            // Check for completed tokens


            $this->tracker->processCompletedTokens(
                $session,
                $this->token->getRespondentId(),
                $this->token->getChangedBy(),
                $this->token->getOrganizationId()
            );

            // Redirect at once, might be another site url
            header('Location: ' . $url);
            exit();
        }

        // No return? Check for old style user based return
        if (!$this->currentUser instanceof User || !$this->currentUser->isActive()) {
            return $this->forward('forward');
        }

        // Check for completed tokens
        $this->tracker->processCompletedTokens($session, $this->token->getRespondentId(), $this->currentUser->getUserId());

        // Get return route parameters
        $url = $this->tokenHelpers->getReturnUrl($this->request, $this->token);
        if ($url) {
            // Default fallback for the fallback
            return $this->getRedirectResponse($url);
        }

        $url = $this->routeHelper->getRouteUrl('respondent.show', [
            MetaModelInterface::REQUEST_ID1 => $this->token->getPatientNumber(),
            MetaModelInterface::REQUEST_ID2 => $this->token->getOrganizationId(),
        ]);
        return $this->getRedirectResponse($url);
    }

    /**
     * Duplicate of to-survey to enable separate rights
     */
    public function takeAction()
    {
        return $this->forward('to-survey');
    }

    /**
     * Old action mentioned on some documentation
     */
    public function tokenAction()
    {
        return $this->forward('index');
    }

    /**
     * Go directly to url
     */
    public function toSurveyAction()
    {
        if (! $this->_initToken()) {
            // Default option
            return $this->forward('index');
        }

        $language = $this->locale->getLanguage();

        try {
            $returnUrl = $this->checkReturnUrl($this->tokenHelpers->getReturnUrl($this->request, $this->token));

            $url  = $this->token->getUrl(
                $language,
                $this->currentUser instanceof User ? $this->currentUser->getUserId() : $this->token->getRespondentId(),
                $returnUrl
            );

            /************************
             * Optional user logout *
             ************************/
            if ($this->currentUser instanceof User && $this->currentUser->isLogoutOnSurvey()) {
                $this->logout();
            }

            // Redirect at once
            return $this->getRedirectResponse($url);

        } catch (SurveyNotFoundException $e) {
            $messenger = $this->request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $messenger->addMessage(sprintf(
                $this->_('The survey for token %s is no longer active.'),
                strtoupper($this->tokenId)
            ));

            // Default option
            return $this->forward('index');
        }
    }
}
