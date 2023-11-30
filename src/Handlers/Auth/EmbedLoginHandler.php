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
use Gems\Repository\RespondentRepository;
use Gems\User\Embed\DeferredRouteHelper;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;

class EmbedLoginHandler implements RequestHandlerInterface
{
    private const MAX_ATTEMPTS_KEY = 'embed_login_max_attempts';

    private readonly RateLimiter $rateLimiter;
    private readonly int $throttleMaxAttempts;
    private readonly int $throttleBlockSeconds;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly DeferredRouteHelper $routeHelper,
        private readonly UserLoader $userLoader,
        private readonly RespondentRepository $respondentRepository,
        HelperAdapter $cacheHelper,
        private readonly array $config,
    ) {
        $this->rateLimiter = new RateLimiter($cacheHelper);
        $this->throttleMaxAttempts = $this->config['embedThrottle']['maxAttempts'] ?? 5;
        $this->throttleBlockSeconds = $this->config['embedThrottle']['blockSeconds'] ?? 600;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
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
                $this->respondentRepository,
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

        if ($result && $result->isValid()) {
            /** @var EmbedIdentity $identity */
            $identity = $result->getIdentity();

            $embeddedUserData = $result->systemUser->getEmbedderData();
            $redirector = $embeddedUserData->getRedirector();

            //if ($redirector instanceof RedirectAbstract) {
            //    $redirector->answerRegistryRequest('request', $this->getRequest());
            //}

            $url = $redirector?->getRedirectUrl(
                $this->routeHelper,
                $result->systemUser,
                $result->deferredUser,
                $identity->getPatientId(),
                [$identity->getOrganizationId()],
            );

            if ($url) {
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

        throw new \Gems\Exception($this->translator->trans("Unable to authenticate"));
    }
}
