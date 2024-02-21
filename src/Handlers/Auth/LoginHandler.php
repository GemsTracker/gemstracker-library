<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\Audit\AuditLog;
use Gems\AuthNew\Adapter\GemsTrackerAuthenticationResult;
use Gems\AuthNew\Adapter\GenericRoutedAuthentication;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\AuthNew\LoginStatusTracker;
use Gems\AuthNew\LoginThrottleBuilder;
use Gems\Layout\LayoutRenderer;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Site\SiteUtil;
use Gems\User\PasswordChecker;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Digits;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorChain;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class LoginHandler implements RequestHandlerInterface
{
    private FlashMessagesInterface $flash;

    private string $loginTemplate = 'gems::login';
    private StatusMessengerInterface $statusMessenger;
    private array $organizations;

    public function __construct(
        private readonly LayoutRenderer $layoutRenderer,
        private readonly TranslatorInterface $translator,
        private readonly SiteUtil $siteUtil,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly LoginThrottleBuilder $loginThrottleBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly Adapter $db,
        private readonly UserLoader $userLoader,
        private readonly AuditLog $auditLog,
        private readonly PasswordChecker $passwordChecker,
        readonly array $config,
    ) {
        if (isset($config['auth']['loginTemplate'])) {
            $this->loginTemplate = $config['auth']['loginTemplate'];
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->auditLog->registerRequest($request);

        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        $this->statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        $siteUrl = $this->siteUtil->getSiteByFullUrl((string)$request->getUri());
        $this->organizations = $siteUrl ? $this->siteUtil->getNamedOrganizationsFromSiteUrl($siteUrl) : [];

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        $data = [
            'trans' => [
                'organization' => $this->translator->trans('Organization'),
                'username' => $this->translator->trans('Username'),
                'password' => $this->translator->trans('Password'),
                'login' => $this->translator->trans('Login'),
            ],
            'organizations' => $this->organizations,
            'input' => $this->flash->getFlash('login_input'),
        ];

        return new HtmlResponse($this->layoutRenderer->renderTemplate($this->loginTemplate, $request, $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        $input = $request->getParsedBody();

        $organizationValidation = new ValidatorChain();
        $organizationValidation->attach(new NotEmpty());
        $organizationValidation->attach(new Digits());
        $organizationValidation->attach(new InArray([
            'haystack' => array_keys($this->organizations),
        ]));

        $notEmptyValidation = new ValidatorChain();
        $notEmptyValidation->attach(new NotEmpty());

        if (
            !$organizationValidation->isValid($input['organization'] ?? null)
            || !$notEmptyValidation->isValid($input['username'] ?? null)
            || !$notEmptyValidation->isValid($input['password'] ?? null)
        ) {
            return $this->redirectBack($request, [$this->translator->trans('Make sure you fill in all fields')]);
        }

        $loginThrottle = $this->loginThrottleBuilder->buildLoginThrottle(
            $input['username'],
            (int)$input['organization'],
        );

        $blockMinutes = $loginThrottle->checkBlock();
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        /** @var GemsTrackerAuthenticationResult $result */
        $result = $authenticationService->authenticate(new GenericRoutedAuthentication(
            $this->userLoader,
            $this->translator,
            $this->db,
            (int)$input['organization'],
            $input['username'],
            $input['password'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
        ));

        $blockMinutes = $loginThrottle->processAuthenticationResult($result);

        if (!$result->isValid()) {
            $messages = $result->getMessages() ?: [$this->translator->trans('The provided credentials are invalid')];
            if ($blockMinutes > 0) {
                $messages[] = $this->blockMessage($blockMinutes);
            }
            return $this->redirectBack($request, $messages);
        }

        /** @var User $user */
        $user = $result->user;
        if (
            $user->isPasswordResetRequired()
            || $this->passwordChecker->reportPasswordWeakness($user, $input['password'])
        ) {
            LoginStatusTracker::make($session, $user)->setPasswordResetActive();
        }

        $this->auditLog->registerUserRequest($request, $user, [sprintf('%s logged in', $user->getLoginName())]);

        return AuthenticationMiddleware::redirectToIntended($authenticationService, $session, $this->urlHelper);
    }

    private function blockMessage(int $minutes)
    {
        return $this->translator->plural(
            'Your account is temporarily blocked, please wait a minute.',
            'Your account is temporarily blocked, please wait %count% minutes.',
            $minutes
        );
    }

    private function redirectBack(ServerRequestInterface $request, array $errors): RedirectResponse
    {
        $input = $request->getParsedBody();

        $this->flash->flash('login_input', [
            'organization' => $input['organization'] ?? null,
            'username' => $input['username'] ?? null,
        ]);

        $this->statusMessenger->addErrors($errors);

        // TODO: Log
        /*// Also log the error to the log table  when the project has logging enabled
        $logErrors = join(' - ', $errors);
        $msg = sprintf(
            'Failed login for : %s (%s) - %s',
            $input['username'],
            $input['organization'],
            $logErrors
        );
        $this->auditLog->logChange($request, $msg);*/

        return new RedirectResponse($request->getUri());
    }
}
