<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\RouteHelper;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Snippets\Ask\MaintenanceModeAskSnippet;
use Gems\Snippets\Ask\ShowAllOpenSnippet;
use Gems\SnippetsActions\Ask\AskInputAction;
use Gems\SnippetsActions\Ask\AskReturnAction;
use Gems\SnippetsActions\Ask\ListTokensAction;
use Gems\SnippetsActions\Ask\LostTokenAction;
use Gems\SnippetsActions\Ask\ToSurveyAction;
use Gems\Tracker;
use Gems\Tracker\Token;
use Gems\Util\Lock\MaintenanceLock;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Translation\Translator;
use Zalt\Base\SymfonyTranslator;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\SnippetsHandler\SnippetHandler;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.0
 */
class AskHandler extends SnippetHandler
{
    use CookieHandlerTrait;

    public static $actions = [
        'forward'   => ListTokensAction::class,
        'index'     => AskInputAction::class,
        'lost'      => LostTokenAction::class,
        'return'    => AskReturnAction::class,
        'take'      => ToSurveyAction::class,
        'to-survey' => ToSurveyAction::class,
    ];

    /**
     * @var array snippet paramater name => value
     */
    protected array $defaultLoopParameters = [];

    /**
     * Usually a child of \Gems\Tracker\Snippets\ShowTokenLoopAbstract,
     * Ask_ShowAllOpenSnippet or Ask_ShowFirstOpenSnippet or
     * a project specific one.
     *
     * @var array of snippet names, presumably \Gems\Tracker\Snippets\ShowTokenLoopAbstract snippets
     */
    protected array $defaultLoopSnippets = [
         ShowAllOpenSnippet::class,
    ];

    /**
     * Snippets displayed when maintenance mode is on
     *
     * @var array
     */
    protected array $maintenanceModeSnippets = [
        MaintenanceModeAskSnippet::class,
    ];

    protected ?Token $token = null;

    protected string $tokenId = '';

    public function __construct(
        SnippetResponderInterface $responder,
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly Locale $locale,
        protected readonly MaintenanceLock $maintenanceLock,
        protected readonly RouteHelper $routeHelper,
        protected readonly Tracker $tracker,
    )
    {
        parent::__construct($responder, $metaModelLoader, $translate);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        $tokenExists = $this->loadToken($request);
        if ($this->maintenanceLock->isLocked()) {
            return $this->responder->getSnippetsResponse($this->maintenanceModeSnippets, ['token' => $this->token]);
        }

        // Implement later. Not yet in use
//        if ($request->getAttribute('resumeLater')) {
//            return $this->responder->getSnippetsResponse($this->resumeLaterSnippets, ['token' => $this->token]);
//        }

        if ($tokenExists) {
            // CHeck if there is no user
            if (! $this->currentUserRepository->hasCurrentUserId()) {
                $language = $this->token->getRespondentLanguage();
                if ($this->locale->isCurrentLanguageDefault() && $this->locale->getLanguage() !== $language) {
                    $this->addSiteCookie(LocaleMiddleware::LOCALE_ATTRIBUTE, $language);
                    $this->locale->setCurrentLanguage($language);

                    if ($this->translate instanceof SymfonyTranslator) {
                        $this->translate->setLocale($language);
                    }
                }
            }

            // Always check here first for tokens to process
            $this->tracker->processCompletedTokens(
                $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE),
                $this->token->getRespondentId(),
                $this->token->getChangedBy(),
                $this->token->getOrganizationId(),
                );
            $this->token->refresh();

        } elseif ($this->tokenId) {
            $messenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            // There is a token but is incorrect
            $messenger->addMessage(sprintf(
                $this->_('The token %s does not exist (any more).'),
                strtoupper($this->tokenId)
            ));

            return new RedirectResponse($this->routeHelper->getRouteUrl('ask.index'));
        }

        return $this->processResponseCookies(parent::handle($request));
    }

    protected function loadToken(ServerRequestInterface $request): bool
    {
        $this->tokenId = $request->getAttribute(MetaModelInterface::REQUEST_ID, '');
        if (! $this->tokenId) {
            return false;
        }

        $this->tokenId = $this->tracker->filterToken($this->tokenId);
        if (! $this->tokenId) {
            return false;
        }

        $this->token = $this->tracker->getToken($this->tokenId);

        return $this->token->exists;
    }

    public function prepareAction(SnippetActionInterface $action): void
    {
        parent::prepareAction($action);

        if (property_exists($action, 'clientIp')) {
            $action->clientIp = $this->request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE);
        }
        if (property_exists($action, 'token')) {
            $action->token = $this->token;
        }
        if ($action instanceof AskInputAction) {
            $action->layoutFixedWidth = 8;
        }
        if ($action instanceof ListTokensAction) {
            $action->defaultLoopParameters = $this->defaultLoopParameters;
            $action->defaultLoopParameters['clientIp'] = $action->clientIp;

            $action->defaultLoopSnippets = $this->defaultLoopSnippets;
        }
    }
}