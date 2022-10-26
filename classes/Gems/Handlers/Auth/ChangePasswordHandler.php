<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AccessLog\AccesslogRepository;
use Gems\AuthNew\PasswordResetThrottleBuilder;
use Gems\DecoratedFlashMessagesInterface;
use Gems\Layout\LayoutRenderer;
use Gems\Site\SiteUtil;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordHandler implements RequestHandlerInterface
{
    private DecoratedFlashMessagesInterface $flash;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly SiteUtil $siteUtil,
        private readonly PasswordResetThrottleBuilder $passwordResetThrottleBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly UserLoader $userLoader,
        private readonly AccesslogRepository $accesslogRepository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        $user = $this->userLoader->getUserByResetKey($request->getAttribute('key'));
        if ($user && $user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) {
            // TODO: Remove NOLOGIN
            $user = null;
        }

        if (!$user || !$user->hasValidResetKey()) {
            if ($user && $user->getLoginName()) {
                $logMessage = sprintf('User %s used old reset key.', $user->getLoginName());
            } else {
                $logMessage = 'Someone used a non existent reset key.';
            }
            $this->accesslogRepository->logChange($request, $logMessage);

            if ($user && ($user->hasPassword() || !$user->isActive())) {
                $userMessage = $this->translator->trans('Your password reset request is no longer valid, please request a new link.');
            } else {
                $userMessage = $this->translator->trans('Your password input request is no longer valid, please request a new link.');
            }
            $this->flash->flashError($userMessage);

            if ($user && $user->isActive()) {
                $this->flash->flash('request_password_reset_input', [
                    'organization' => $user->getBaseOrganizationId(),
                    'username' => $user->getLoginName(),
                ]);
            }

            return new RedirectResponse($this->urlHelper->generate('auth.password-reset.request'));
        }

        if (
            !$user->isActive()
            || !$user->canResetPassword()
            || !$user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)
        ) {
            $this->flash->flashError($this->translator->trans('You cannot reset your password.'));
            return new RedirectResponse($request->getUri());
        }

        if ($request->getMethod() !== 'POST') {
            $this->accesslogRepository->logChange($request, sprintf("User %s opened valid reset link.", $user->getLoginName()));

            $data = [];

            return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::change-password', $request, $data));
        }

        $input = $request->getParsedBody();

        $newPasswordValidator = new \Gems\User\Validate\NewPasswordValidator($user);
        if (!$newPasswordValidator->isValid($input['new_password'] ?? null)) {
            $this->flash->flashErrors($newPasswordValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $newConfirmValidator = new \MUtil\Validate\IsConfirmed('repeat_password', $this->translator->trans('Repeat password'));
        $newConfirmValidator->setMessage(
            $this->translator->trans('Must be the same as %fieldDescription%.'),
            \MUtil\Validate\IsConfirmed::NOT_SAME
        );
        if (!$newConfirmValidator->isValid($input['new_password'] ?? null, $input)) {
            $this->flash->flashErrors($newConfirmValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $repeatConfirmValidator = new \MUtil\Validate\IsConfirmed('new_password', $this->translator->trans('New password'));
        $repeatConfirmValidator->setMessage(
            $this->translator->trans('Must be the same as %fieldDescription%.'),
            \MUtil\Validate\IsConfirmed::NOT_SAME
        );
        if (!$repeatConfirmValidator->isValid($input['repeat_password'] ?? null, $input)) {
            $this->flash->flashErrors($repeatConfirmValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $user->setPassword($input['new_password']);

        $this->flash->flashSuccess($this->translator->trans('New password is active.'));
        $this->accesslogRepository->logChange($request, $this->translator->trans('User logged in through reset password.'));

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }
}
