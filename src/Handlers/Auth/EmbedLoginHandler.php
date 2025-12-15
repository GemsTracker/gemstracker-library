<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

// use Gems\Audit\AuditLog;
use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\AuthNew\Adapter\EmbedAuthentication;
use Gems\AuthNew\Adapter\EmbedAuthenticationResult;
use Gems\AuthNew\Adapter\EmbedIdentity;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Gems\CookieResponse;
use Gems\Log\Loggers;
use Gems\Middleware\ClientIpMiddleware;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Repository\EmbeddedUserRepository;
use Gems\User\Embed\DeferredRouteHelper;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Log\LoggerInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class EmbedLoginHandler implements RequestHandlerInterface
{
    private const MAX_ATTEMPTS_KEY = 'embed_login_max_attempts';

    protected LoggerInterface $logger;

    private RateLimiter $rateLimiter;

    private bool $showKeyInLog = false;

    private StatusMessengerInterface $statusMessenger;

    private int $throttleMaxAttempts;
    private int $throttleBlockSeconds;

    public function __construct(
        private readonly TranslatorInterface          $translator,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly EmbeddedUserRepository       $embeddedUserRepository,
        private readonly DeferredRouteHelper          $routeHelper,
        private readonly UserLoader                   $userLoader,
        private readonly array                        $config,
        HelperAdapter                                 $cacheHelper,
        Loggers                                       $loggers,
    ) {
        $this->rateLimiter = new RateLimiter($cacheHelper);
        $this->throttleMaxAttempts = $this->config['embedThrottle']['maxAttempts'] ?? 5;
        $this->throttleBlockSeconds = $this->config['embedThrottle']['blockSeconds'] ?? 600;

        $this->logger = $loggers->getLogger('embeddedLoginLog');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->embeddedUserRepository->setRequest($request);
        $this->statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        try {
            /**
             * @var SessionInterface $session
             */
            $session = $request->getAttribute(SessionInterface::class);
            $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

            $input = $request->getQueryParams();
            if ($request->getMethod() === 'POST') {
                $input = array_merge($input, $request->getParsedBody() ?? []);
            }

            $result = null;

            if ($this->rateLimiter->tooManyAttempts(self::MAX_ATTEMPTS_KEY, $this->throttleMaxAttempts)) {
                throw new \Gems\Exception($this->translator->trans("Too many login attempts"));
            }

            // Backwards compatibility with old id types
            if (isset($input['id1'], $input['id2'], $input['id3'], $input['id4'])) {
                $input['org'] = $input['id1'];
                $input['key'] = $input['id2'];
                $input['epd'] = $input['id3'];
                $input['usr'] = $input['id4'];
                unset($input['id1']);
                unset($input['id2']);
                unset($input['id3']);
                unset($input['id4']);
            }

            if (isset($input['patientId'])) {
                $input['pid'] = $input['patientId'];
                unset($input['patientId']);
            }

            $logKey = null;
            if ($this->showKeyInLog) {
                $logKey = $input['key'];
            }

            $this->logInfo(sprintf(
                "Login user: %s, organization: %s, end user: %s, patient: %s, key: %s",
                $input['epd'] ?? 'n/a',
                $input['org'] ?? 'n/a',
                $input['usr'] ?? 'n/a',
                $input['pid'] ?? 'n/a',
                $logKey
            ));
            // TODO: org should be an existing organization?

            if (!empty($input['epd'])
                && !empty($input['key'])
                && !empty($input['org'])
                && ctype_digit($input['org'])
            ) {
                /** @var EmbedAuthenticationResult $result */
                $result = $authenticationService->authenticate(new EmbedAuthentication(
                    $this->userLoader,
                    $input['epd'],
                    $input['key'],
                    $input['usr'] ?? '',
                    $input['pid'] ?? '',
                    (int)$input['org'],
                    $request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE)
                ));

                if (!$result->isValid() && $result->getCode() !== AuthenticationResult::FAILURE_DEFERRED) {
                    $this->rateLimiter->hit(self::MAX_ATTEMPTS_KEY, $this->throttleBlockSeconds);
                }

                if (!$result->isValid()) {
                    $this->logInfo(sprintf(
                        "Login failed: %s",
                        implode('; ', $result->getMessages())
                    ));
                }
            }

            if ($result && $result->isValid()) {
                /** @var EmbedIdentity $identity */
                $identity = $result->getIdentity();

                $this->embeddedUserRepository->setPatientNr($identity->getPatientId(), $identity->getOrganizationId());
                $embeddedUserData = $this->userLoader->getEmbedderData($result->systemUser);
                $redirector = $embeddedUserData->getRedirector();

                //if ($redirector instanceof RedirectAbstract) {
                //    $redirector->answerRegistryRequest('request', $this->getRequest());
                //}

                $url = $redirector?->getRedirectUrl(
                    $request,
                    $this->routeHelper,
                    $result->systemUser,
                    $result->deferredUser,
                    $identity->getPatientId(),
                    [$identity->getOrganizationId()],
                );

                if ($url) {
                    if ($url instanceof RedirectResponse) {
                        $this->logInfo(sprintf(
                            "Login for end user: %s, patient: %s successful, redirecting...",
                            $identity->getLoginName(),
                            $identity->getPatientId()
                        ));
                        $response = $url;
                    } else {
                        $this->logInfo(sprintf(
                            "Login for end user: %s, patient: %s successful, redirecting to: %s",
                            $identity->getLoginName(),
                            $identity->getPatientId(),
                            $url
                        ));
                        $response = new RedirectResponse($url);
                    }
                    return CookieResponse::addCookieToResponse(
                        $request,
                        $response,
                        CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE,
                        $identity->getOrganizationId()
                    );
                }
            }

            /* Debug help
            if (isset($input['key'])) {
                $input['key'] = '*******';
            }

            $messages = [];
            if ($result) {
                $messages = $result->getMessages();
            }

            return new JsonResponse([
                'error' => 'Unable to Authenticate',
                'usedInput' => $input,
                'result' => $messages,
            ], 401);*/
            $publicMessages = [];
            if ($result instanceof EmbedAuthenticationResult) {
                $publicMessages = $result->publicMessages;
            }
            $error = $this->translator->_("Unable to authenticate");
            if (!empty($publicMessages)) {
                $error .= ': ';
                foreach ($publicMessages as $message) {
                    $error .= $this->translator->_($message) . ' ';
                }
            }

            $this->logInfo("Unable to authenticate");
            $this->statusMessenger->addError($error);
        } catch (\Exception $e) {
            $this->logInfo($e->getMessage());
            $this->statusMessenger->addError($e->getMessage());

        }

        $homeUrl = $this->routeHelper->getRouteUrl( 'auth.logout');
        return new RedirectResponse($homeUrl);
    }

    /**
     * @param  string   $message   Message to log
     */
    public function logInfo(string $message): void
    {
        try {
            // dump($message);
            $this->logger->notice($message);
        } catch(\Exception $e) {
            error_log($e->getMessage());
            error_log($message);
        }
    }
}
