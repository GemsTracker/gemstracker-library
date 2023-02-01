<?php

namespace Gems\AuthNew;

use Gems\AuthTfa\OtpMethodBuilder;
use Gems\AuthTfa\TfaService;
use Gems\Handlers\ChangeGroupHandler;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public const CURRENT_USER_ATTRIBUTE = 'current_user';
    public const CURRENT_IDENTITY_ATTRIBUTE = 'current_identity';


    private const LOGIN_INTENDED_URL_SESSION_KEY = 'login_intended_url';

    protected const CHECK_TFA = true;

    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly OtpMethodBuilder $otpMethodBuilder,
        private readonly RouterInterface $router,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        if ($session === null) {
            throw new \LogicException('Session middleware should be executed before authentication middleware');
        }

        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $user = $authenticationService->getLoggedInUser();

        if (!$authenticationService->isLoggedIn() || !$authenticationService->checkValid(true, $user)) {
            return $this->redirectWithIntended(null, $request, $this->router->generateUri('auth.login'));
        }

        if (static::CHECK_TFA) {
            $tfaService = new TfaService($session, $authenticationService, $this->otpMethodBuilder);
            if ($tfaService->requiresAuthentication($user, $request)) {
                return $this->redirectWithIntended($user, $request, $this->router->generateUri('tfa.login'));
            }

            if (null !== ($currentGroupId = $session->get(ChangeGroupHandler::CURRENT_USER_GROUP_ATTRIBUTE))) {
                $allowedGroups = $user->getAllowedStaffGroups(false);

                if (isset($allowedGroups[$currentGroupId])) {
                    $user->setCurrentGroupId($currentGroupId);
                }
            }

            $request = $request->withAttribute(self::CURRENT_USER_ATTRIBUTE, $user);
            $request = $request->withAttribute(self::CURRENT_IDENTITY_ATTRIBUTE, $authenticationService->getIdentity());
        } else {
            $request = $request->withAttribute('current_user_without_tfa', $user);
        }

        if (!$user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)) {
            $authenticationService->logout();
            if (isset($tfaService)) {
                $tfaService->logout();
            }

            /** @var StatusMessengerInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $flash?->addErrors([
                $this->translator->trans('You are not allowed to login from this location.'),
            ]);

            return $this->redirectWithIntended($user, $request, $this->router->generateUri('auth.login'));
        }

        $loginStatusTracker = LoginStatusTracker::make($session, $user);
        if ($loginStatusTracker->isPasswordResetActive()) {
            /** @var RouteResult $routeResult */
            $routeResult = $request->getAttribute(RouteResult::class);
            if (!in_array($routeResult->getMatchedRouteName(), [
                'auth.change-password',
                'tfa.login',
                'auth.logout',
            ])) {
                /** @var StatusMessengerInterface $flash */
                $flash = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
                $flash?->addErrors([
                    $this->translator->trans('Your password must be changed.'),
                ]);

                return $this->redirectWithIntended($user, $request, $this->router->generateUri('auth.change-password'));
            }
        } elseif (static::CHECK_TFA && $loginStatusTracker->isRequireAppTotpActive()) {
            /** @var RouteResult $routeResult */
            $routeResult = $request->getAttribute(RouteResult::class);
            if (!in_array($routeResult->getMatchedRouteName(), [
                'auth.set-tfa',
                'tfa.login',
                'auth.logout',
            ])) {
                /** @var StatusMessengerInterface $flash */
                $flash = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
                $flash?->addInfos([
                    $this->translator->trans('Please configure Softtoken TFA to continue.'),
                ]);

                return $this->redirectWithIntended($user, $request, $this->router->generateUri('auth.set-tfa'));
            }
        }

        return $handler->handle($request);
    }

    private function redirectWithIntended(
        ?User $user,
        ServerRequestInterface $request,
        string $url
    ): RedirectResponse {
        $session = $request->getAttribute(SessionInterface::class);

        if ($request->getMethod() === 'GET') {
            self::registerIntended($user, $session, (string)$request->getUri());
        }

        return new RedirectResponse($url);
    }

    public static function registerIntended(
        ?User $user,
        SessionInterface $session,
        string $intendedUrl,
    ) {
        $session->set(self::LOGIN_INTENDED_URL_SESSION_KEY, [
            'url' => $intendedUrl,
            'loginname' => $user?->getLoginName(),
        ]);
    }

    public static function redirectToIntended(
        AuthenticationService $authenticationService,
        SessionInterface $session,
        UrlHelper $urlHelper
    ): RedirectResponse {
        if ($session->has(self::LOGIN_INTENDED_URL_SESSION_KEY)) {
            $loginName = $authenticationService->getIdentity()?->getLoginName();

            $loginRedirect = $session->get(self::LOGIN_INTENDED_URL_SESSION_KEY);

            $session->unset(self::LOGIN_INTENDED_URL_SESSION_KEY);

            if (empty($loginRedirect['loginname']) || $loginRedirect['loginname'] === $loginName) {
                return new RedirectResponse($loginRedirect['url']);
            }
        }

        return new RedirectResponse($urlHelper->generate('track-builder.source.index')); // TODO: Which route?
    }
}
