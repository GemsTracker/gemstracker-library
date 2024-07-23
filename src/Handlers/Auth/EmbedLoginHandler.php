<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\AuthNew\Adapter\EmbedAuthentication;
use Gems\AuthNew\Adapter\EmbedAuthenticationResult;
use Gems\AuthNew\Adapter\EmbedIdentity;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\Cache\HelperAdapter;
use Gems\Cache\RateLimiter;
use Gems\Log\Loggers;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\FlashMessageMiddleware;
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

    private StatusMessengerInterface $statusMessenger;

    private int $throttleMaxAttempts;
    private int $throttleBlockSeconds;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly DeferredRouteHelper $routeHelper,
        private readonly UserLoader $userLoader,
        private readonly array $config,
        HelperAdapter $cacheHelper,
        Loggers $loggers,
    ) {
        $this->rateLimiter = new RateLimiter($cacheHelper);
        $this->throttleMaxAttempts = $this->config['embedThrottle']['maxAttempts'] ?? 5;
        $this->throttleBlockSeconds = $this->config['embedThrottle']['blockSeconds'] ?? 600;

        $this->logger = $loggers->getLogger('embeddedLoginLog');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        try {
            $session = $request->getAttribute(SessionInterface::class);
            $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

            $input = ($request->getMethod() === 'POST') ? $request->getParsedBody() : $request->getQueryParams();

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

            $this->logInfo(sprintf(
                "Login user: %s, end user: %s, patient: %s, key: %s",
                $input['epd'],
                $input['usr'],
                $input['pid'],
                $input['key']
            ));
            // TODO: org should be an existing organization?

            if (!empty($input['epd'])
                && !empty($input['key'])
                && !empty($input['usr'])
                && !empty($input['pid'])
                && !empty($input['org'])
                && ctype_digit($input['org'])
            ) {
                /** @var EmbedAuthenticationResult $result */
                $result = $authenticationService->authenticate(new EmbedAuthentication(
                    $this->userLoader,
                    $input['epd'],
                    $input['key'],
                    $input['usr'],
                    $input['pid'],
                    (int)$input['org'],
                ));

                if (!$result->isValid() && $result->getCode() !== AuthenticationResult::FAILURE_DEFERRED) {
                    $this->rateLimiter->hit(self::MAX_ATTEMPTS_KEY, $this->throttleBlockSeconds);
                }
            }
            $request = $request->withAttribute(CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE, (int)$input['org']);
//            file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($input, true) . "\n", FILE_APPEND);

            if ($result && $result->isValid()) {
                /** @var EmbedIdentity $identity */
                $identity = $result->getIdentity();

                $embeddedUserData = $this->userLoader->getEmbedderData($result->systemUser);
                $redirector = $embeddedUserData->getRedirector();
//                file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  get_class($embeddedUserData) . "\n", FILE_APPEND);
//                file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  get_class($redirector) . "\n", FILE_APPEND);

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
                            "Login for end user: %s, patient: %s successful, redirecting to: %s",
                            $input['usr'],
                            $input['pid'],
                            $url->getBody(),
                        ));
                        return $url;
                    }
                    $this->logInfo(sprintf(
                        "Login for end user: %s, patient: %s successful, redirecting to: %s",
                        $input['usr'],
                        $input['pid'],
                        $url
                    ));

                    return new RedirectResponse($url);
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
            $this->logInfo("Unable to authenticate");
            $this->statusMessenger->addError($this->translator->_("Unable to authenticate"));
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
