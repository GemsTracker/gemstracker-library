<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AccessLog\AccesslogRepository;
use Gems\AuthNew\PasswordResetThrottleBuilder;
use Gems\Communication\CommunicationRepository;
use Gems\DecoratedFlashMessagesInterface;
use Gems\Layout\LayoutRenderer;
use Gems\Site\SiteUtil;
use Gems\User\UserLoader;
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
        private readonly PasswordResetThrottleBuilder $passwordResetThrottleBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly UserLoader $userLoader,
        private readonly AccesslogRepository $accesslogRepository,
        private readonly CommunicationRepository $communicationRepository,
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

        $passwordResetThrottle = $this->passwordResetThrottleBuilder->buildPasswordResetThrottle(
            $request->getServerParams()['REMOTE_ADDR'] ?? '',
            (int)$input['organization'],
        );

        $blockMinutes = $passwordResetThrottle->checkBlock();
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        $user = $this->userLoader->getUser($input['username'], (int)$input['organization']);
        if ($user && $user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) {
            // TODO: Remove NOLOGIN
            $user = null;
        }

        if (
            $user !== null
            && $user->isActive()
            && $user->canResetPassword()
            && $user->isAllowedOrganization((int)$input['organization'])
            && $user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)
        ) {
            $errors = $this->sendUserResetEMail($user);

            if ($errors) {
                $this->accesslogRepository->logChange(
                    $request,
                    sprintf(
                        "User %s requested reset password but got %d error(s). %s",
                        $input['username'],
                        count($errors),
                        implode(' ', $errors)
                    )
                );
            }

            $this->accesslogRepository->logChange($request);
        }

        $this->flash->flashInfo($this->translator->trans(
            'If the entered username or e-mail is valid, we have sent you an e-mail with a reset link. Click on the link in the e-mail.'
        ));

        $passwordResetThrottle->registerAttempt();
        $blockMinutes = $passwordResetThrottle->checkBlock();
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }

    private function blockMessage(int $minutes)
    {
        return $this->translator->plural(
            'You have attempted a password reset multiple times. Please wait a minute before trying again.',
            'You have attempted a password reset multiple times. Please wait %count% minutes before trying again.',
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
        $templateId = $this->communicationRepository->getResetPasswordTemplate($user->getBaseOrganization());
        if ($templateId) {
            [
                'subject' => $subjectTemplate,
                'gctt_body' => $bodyTemplate
            ] = $this->communicationRepository->getCommunicationTexts($templateId, $user->getLocale());
        } else {
            $subjectTemplate = $this->translator->trans('Password reset requested');

            // Multi line strings did not come through correctly in poEdit
            $bodyTemplate = $this->translator->trans("Dear {{greeting}},<br><br><br>A new password was requested for your <strong>{{organization}}</strong> account on the <strong>{{project}}</strong> site, please click within {{reset_in_hours}} hours on <a href=\"{{reset_url}}\">this link</a> to enter the password of your choice.<br><br><br>{{organization_signature}}<br><br><a href=\"{{reset_url}}\">{{reset_url}}</a><br>");
        }

        return $user->sendMail($subjectTemplate, $bodyTemplate, true);
    }
}
