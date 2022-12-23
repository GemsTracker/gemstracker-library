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

use Gems\Handlers\SnippetLegacyHandlerAbstract;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\MenuNew\RouteHelper;
use Gems\MenuNew\RouteNotFoundException;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Project\ProjectSettings;
use Gems\Tracker;
use \Gems\Tracker\Form\AskTokenForm;
use Gems\Tracker\Token;
use Gems\Util\MaintenanceLock;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionMiddleware;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
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
    /**
     * @var \Gems\User\Organization
     */
    public $currentOrganization;

    /**
     *
     * @var \Gems\User\User
     */
    public $currentUser;

    /**
     * Usually a child of \Gems\Tracker\Snippets\ShowTokenLoopAbstract,
     * Ask_ShowAllOpenSnippet or Ask_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array Or string of snippet names, presumably \Gems\Tracker\Snippets\ShowTokenLoopAbstract snippets
     */
    // protected $forwardSnippets = 'Ask\\ShowAllOpenSnippet';
    // protected $forwardSnippets = 'Ask\\RedirectUntilGoodbyeSnippet';
    protected $forwardSnippets = 'Ask\\ShowFirstOpenSnippet';

    /**
     * The width factor for the label elements.
     *
     * Width = (max(characters in labels) * labelWidthFactor) . 'em'
     *
     * @var float
     */
    protected $labelWidthFactor = 0.8;

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
    protected $lostParameters = [];

    /**
     * The snippets used for the lost action.
     *
     * @var mixed String or array of snippets name
     */
    protected $lostSnippets = 'Token\\TokenForgottenSnippet';

    /**
     * Snippets displayed when maintenance mode is on
     *
     * @var array
     */
    protected $maintenanceModeSnippets = ['Ask\\MaintenanceModeAskSnippet'];

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
    protected $resumeLaterParameters = [];

    /**
     * Usually a child of \Gems\Tracker\Snippets\ShowTokenLoopAbstract,
     * Ask_ShowAllOpenSnippet or Ask_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array Or string of snippet names, presumably \Gems\Tracker\Snippets\ShowTokenLoopAbstract snippets
     */
    protected $resumeLaterSnippets = 'Ask\\ResumeLaterSnippet';

    protected ?string $tokenId = null;

    /**
     * The current token
     *
     * set by _initToken()
     *
     * @var \Gems\Tracker\Token
     */
    protected $token;

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
    ) {
        parent::__construct($responder, $translate);
        $this->currentUser = $currentUserRepository->getCurrentUser();

    }

    /**
     * Common handler utility to initialize tokens from parameters
     *
     * @return boolean True if there is a real token specified in the request
     */
    protected function _initToken()
    {
        if ($this->tracker && $this->token instanceof Token) {
            return $this->token && $this->token->exists;
        }

        $tokenId = $this->request->getAttribute(Model::REQUEST_ID);
        if (null === $tokenId && $this->requestInfo->isPost()) {
            $postData = $this->request->getParsedBody();
            if (isset($postData[Model::REQUEST_ID])) {
                $tokenId = $postData[Model::REQUEST_ID];
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

        //if (! \Gems\Cookies::getLocale($this->getRequest())) {
        if (! ($this->currentUser->isActive() || $this->token->getSurvey()->isTakenByStaff())) {
            $tokenLang = strtolower($this->token->getRespondentLanguage());
            $tokenOrg = $this->token->getOrganization();

            if ($tokenOrg->getId() != $this->currentOrganization->getId()) {
                $this->currentUser->setCurrentOrganization($tokenOrg);
            }
            // \MUtil\EchoOut\EchoOut::track($tokenLang, $this->locale->getLanguage());
            if ($tokenLang != $this->locale->getLanguage()) {
                if ($this->currentUser->switchLocale($tokenLang)) {
                    // Reload url as the menu has already been loaded in the previous language
                    $url = $tokenOrg->getLoginUrl() . '/ask/forward/' . $this->tokenId;
                    $this->redirectUrl = $url;
                }
            }
        }
        //}

        return true;
    }

    /**
     *
     * @param array $input
     * @return array
     */
    protected function _processAskParameters(array $input)
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
            $this->_('Tokens identify a survey that was assigned to you personally.') . ' AskHandler.php' . $this->_('Entering the token and pressing OK will open that survey.'));

        if ($this->currentUser->isActive()) {
            if ($this->currentUser->isLogoutOnSurvey()) {
                $this->html->pInfo($this->_('After answering the survey you will be logged off automatically.'));
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
        return new RedirectResponse($this->getActionUrl($action));
    }

    /**
     * Show the user a screen with token information and a button to take at least one survey
     *
     * @return void
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
            return;
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
        if ($screen) {
            $params   = $screen->getParameters($this->token);
            $snippets = $screen->getSnippets($this->token);
            if (false !== $snippets) {
                $this->forwardSnippets = $snippets;
            }
        }
        $params['token'] = $this->token;

        $params['requestInfo'] = $this->getRequestInfo();

        // Display token when possible
        if ($this->html->snippet($this->forwardSnippets, $params)) {
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
        return new RedirectResponse($url);
    }

    protected function getActionUrl(string $action): string
    {
        $currentRouteName = $this->requestInfo->getRouteName();
        $routeParts = explode('.', $currentRouteName);
        array_pop($routeParts);
        $routeParts[] = $action;

        $newRouteName = join('.', $routeParts);
        $newRouteInfo = $this->routeHelper->getRoute($newRouteName);
        $newRouteParams = $this->routeHelper->getRouteParamsFromKnownParams($newRouteInfo, $this->requestInfo->getRequestMatchedParams());

        return $this->routeHelper->getRouteUrl($newRouteName, $newRouteParams);
    }

    public function getRequestInfo(): \MUtil\Request\RequestInfo
    {
        $factory = new \MUtil\Request\RequestInfoFactory($this->request);
        return $factory->getRequestInfo();
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

        // Make sure to return to the forward screen
        $this->currentUser->setSurveyReturn();

        $form    = $this->tracker->getAskTokenForm([
            'displayOrder' => [
                'element',
                'description',
                'errors',
            ],
            'labelWidthFactor' => 0.8,
            'clientIp' => $this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE),
        ]);

        if ($this->requestInfo->isPost() && $form->isValid($this->request->getParsedBody(), false)) {
            $this->forwardAction();
            return;
        }

        $form->populate($this->request->getParsedBody());
        $this->displayTokenForm($form);
    }

    /**
     * Show lost token screen for respondents
     */
    public function lostAction()
    {
        $this->addSnippet($this->lostSnippets, $this->_processAskParameters($this->lostParameters));
    }

    /**
     * If the user signalled to resume later
     */
    public function resumeLaterAction()
    {
        $this->addSnippet($this->resumeLaterSnippets, $this->_processAskParameters($this->resumeLaterParameters));
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

        if ((! $this->currentUser->isActive()) && $this->requestInfo->getParam('resumeLater', 0)) {
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
        if (! $this->currentUser->isActive()) {
            return $this->forward('forward');
        }

        // Check for completed tokens
        $this->tracker->processCompletedTokens($session, $this->token->getRespondentId(), $this->currentUser->getUserId());

        // Get return route parameters
        $url = $this->currentUser->getSurveyReturn();
        if ($url) {
            // Default fallback for the fallback
            return new RedirectResponse($url);
        }

        $url = $this->routeHelper->getRouteUrl('respondent.show', [
            Model::REQUEST_ID1 => $this->token->getPatientNumber(),
            Model::REQUEST_ID2 => $this->token->getOrganizationId(),
        ]);
        return new RedirectResponse($url);
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
            $url  = $this->token->getUrl(
                $language,
                $this->currentUser->getUserId() ? $this->currentUser->getUserId() : $this->token->getRespondentId()
            );

            /************************
             * Optional user logout *
             ************************/
            if ($this->currentUser->isLogoutOnSurvey()) {
                $this->currentUser->unsetAsCurrentUser();
            }

            // Redirect at once
            header('Location: ' . $url);
            exit();

        } catch (\Gems\Tracker\Source\SurveyNotFoundException $e) {
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
