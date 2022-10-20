<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AccessLog\AccesslogRepository;
use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\AuthNew\GenericFailedAuthenticationResult;
use Gems\AuthNew\LoginThrottleBuilder;
use Gems\DecoratedFlashMessagesInterface;
use Gems\Layout\LayoutRenderer;
use Gems\Site\SiteUtil;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Digits;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorChain;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestPasswordResetHandler implements RequestHandlerInterface
{
    private DecoratedFlashMessagesInterface $flash;
    private array $organizations;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly SiteUtil $siteUtil,
        private readonly LoginThrottleBuilder $loginThrottleBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly Adapter $db,
        private readonly UserLoader $userLoader,
        private readonly AccesslogRepository $accesslogRepository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $siteUrl = $this->siteUtil->getSiteByFullUrl((string)$request->getUri());
        $this->organizations = $siteUrl ? $this->siteUtil->getNamedOrganizationsFromSiteUrl($siteUrl) : [];

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        $data = [
            'organizations' => $this->organizations,
        ];

        return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::request-password-reset', $request, $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
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
        ) {
            return $this->redirectBack($request, [$this->translator->trans('Make sure you fill in all fields')]);
        }

        // TODO We do not want the login throttle as we do not want to throttle on username but on IP only (Throttle in DB)
        $loginThrottle = $this->loginThrottleBuilder->buildLoginThrottle(
            $input['username'],
            (int)$input['organization'],
        );

        $blockMinutes = $loginThrottle->checkBlock();
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        $user = $this->userLoader->getUser($input['username'], (int)$input['organization']);
        if ($user && $user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) {
            // TODO: Remove NOLOGIN
            $user = null;
        }

        $result = new GenericFailedAuthenticationResult(AuthenticationResult::FAILURE);
        if ($user && !$user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)) {
            $result = new GenericFailedAuthenticationResult(AuthenticationResult::DISALLOWED_IP);
        } elseif (
            $user !== null
            && $user->isActive()
            && $user->canResetPassword()
            && $user->isAllowedOrganization($context['organization'])
            && $user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)
        ) {
            $errors = $this->sendUserResetEMail($user);

            if ($errors) {
                $this->accesslog->logChange(
                    $request,
                    sprintf(
                        "User %s requested reset password but got %d error(s). %s",
                        $input['username'],
                        count($errors),
                        implode(' ', $errors)
                    )
                );
            }

            $this->accesslog->logChange($request);
        }

        $this->flash->flashInfo($this->translator->trans(
            'If the entered username or e-mail is valid, we have sent you an e-mail with a reset link. Click on the link in the e-mail.'
        ));

        $blockMinutes = $loginThrottle->processAuthenticationResult($result);
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
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
        $this->flash->flashErrors($errors);

        return new RedirectResponse($request->getUri());
    }

    /**
     * Send the user an e-mail with a link for password reset
     *
     * @param \Gems\User\User $user
     * @return mixed string or array of Errors or null when successful.
     */
    public function sendUserResetEMail(\Gems\User\User $user)
    {
        $subjectTemplate = $this->translator->trans('Password reset requested');

        // Multi line strings did not come through correctly in poEdit
        $bbBodyTemplate = $this->translator->trans("Dear {greeting},\n\n\nA new password was requested for your [b]{organization}[/b] account on the [b]{project}[/b] site, please click within {reset_in_hours} hours on [url={reset_url}]this link[/url] to enter the password of your choice.\n\n\n{organization_signature}\n\n[url={reset_url}]{reset_url}[/url]\n");

        return $user->sendMail($subjectTemplate, $bbBodyTemplate, true);
    }
}
