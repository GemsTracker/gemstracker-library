<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\Audit\AccesslogRepository;
use Gems\Layout\LayoutRenderer;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Session\ValidationMessenger;
use Gems\User\PasswordChecker;
use Gems\User\PasswordHistoryChecker;
use Gems\User\UserLoader;
use Gems\User\Validate\NewPasswordValidator;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use MUtil\Validator\IsConfirmed;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class ResetPasswordChangeHandler implements RequestHandlerInterface
{
    private StatusMessengerInterface $statusMessenger;
    private ValidationMessenger $validationMessenger;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly UrlHelper $urlHelper,
        private readonly UserLoader $userLoader,
        private readonly AccesslogRepository $accesslogRepository,
        private readonly PasswordChecker $passwordChecker,
        private readonly PasswordHistoryChecker $passwordHistoryChecker,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $this->validationMessenger = new ValidationMessenger($request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE));
        $user = $this->userLoader->getUserByResetKey($request->getAttribute('key'));

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
            $this->statusMessenger->addError($userMessage);

            /*if ($user && $user->isActive()) {
                $this->flash->flash('request_password_reset_input', [
                    'organization' => $user->getBaseOrganizationId(),
                    'username' => $user->getLoginName(),
                ]);
            }*/

            return new RedirectResponse($this->urlHelper->generate('auth.password-reset.request'));
        }

        if (
            !$user->isActive()
            || !$user->canResetPassword()
            || !$user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)
        ) {
            $this->statusMessenger->addError($this->translator->trans('You cannot reset your password.'));
            return new RedirectResponse($this->urlHelper->generate('auth.password-reset.request'));
        }

        if ($request->getMethod() !== 'POST') {
            $this->accesslogRepository->logChange($request, sprintf("User %s opened valid reset link.", $user->getLoginName()));

            $data = [
                'ask_old' => false,
                'rules' => $this->passwordChecker->reportPasswordWeakness($user, null, true),
                'validationMessenger' => $this->validationMessenger,
            ];

            return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::change-password', $request, $data));
        }

        $input = $request->getParsedBody();

        $newPasswordValidator = new NewPasswordValidator($user, $this->passwordChecker, $this->passwordHistoryChecker);
        if (!$newPasswordValidator->isValid($input['new_password'] ?? null)) {
            $this->statusMessenger->addError($this->translator->trans('Password reset failed.'), true);
            $this->validationMessenger->addValidationErrors('new_password', $newPasswordValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $repeatConfirmValidator = new IsConfirmed('new_password', $this->translator->trans('New password'));
        $repeatConfirmValidator->setMessage(
            $this->translator->trans('Must be the same as %fieldDescription%.'),
            IsConfirmed::NOT_SAME
        );
        if (!$repeatConfirmValidator->isValid($input['repeat_password'] ?? null, $input)) {
            $this->statusMessenger->addError($this->translator->trans('Password reset failed.'), true);
            $this->validationMessenger->addValidationErrors('repeat_password', $repeatConfirmValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $user->setPassword($input['new_password']);

        $this->statusMessenger->addSuccess($this->translator->trans('New password is active.'));
        $this->accesslogRepository->logChange($request, $this->translator->trans('User reset password.'));

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }
}
