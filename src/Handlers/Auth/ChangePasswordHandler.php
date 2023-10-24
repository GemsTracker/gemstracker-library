<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\Audit\AccesslogRepository;
use Gems\AuthNew\Adapter\GemsTrackerAuthentication;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\LoginStatusTracker;
use Gems\Layout\LayoutRenderer;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Session\ValidationMessenger;
use Gems\User\PasswordChecker;
use Gems\User\User;
use Laminas\Db\Adapter\Adapter;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class ChangePasswordHandler implements RequestHandlerInterface
{
    private ValidationMessenger $validationMessenger;

    private StatusMessengerInterface $statusMessenger;

    public function __construct(
        private readonly Adapter $db,
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly PasswordChecker $passwordChecker,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validationMessenger = new ValidationMessenger($request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE));
        $this->statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        /** @var User $user */
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

        if (
            !$user->isActive()
            || !$user->canResetPassword()
            || !$user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)
        ) {
            $this->statusMessenger->addError($this->translator->trans('You cannot reset your password.'), true);
            if ($request->getMethod() === 'POST') {
                return new RedirectResponse($request->getUri());
            }
        }

        if ($request->getMethod() !== 'POST') {
            $data = [
                'ask_old' => true,
                'rules' => $this->passwordChecker->reportPasswordWeakness($user, null, true),
                'validationMessenger' => $this->validationMessenger,
            ];

            return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::change-password', $request, $data));
        }

        $input = $request->getParsedBody();

        $authResult = GemsTrackerAuthentication::fromUser(
            $this->db,
            $user,
            $input['old_password'] ?? null
        )->authenticate();

        if (!$authResult->isValid()) {
            $this->statusMessenger->addError($this->translator->trans('Password reset failed.'), true);
            $this->validationMessenger->addValidationErrors('old_password', [$this->translator->trans('Wrong password.')]);
            return new RedirectResponse($request->getUri());
        }

        // TODO: generalize forms
        $newPasswordValidator = new \Gems\User\Validate\NewPasswordValidator($user, $this->passwordChecker);
        if (!$newPasswordValidator->isValid($input['new_password'] ?? null)) {
            $this->statusMessenger->addError($this->translator->trans('Password reset failed.'), true);
            $this->validationMessenger->addValidationErrors('new_password', $newPasswordValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $repeatConfirmValidator = new \MUtil\Validator\IsConfirmed('new_password', $this->translator->trans('New password'));
        $repeatConfirmValidator->setMessage(
            $this->translator->trans('Must be the same as %fieldDescription%.'),
            \MUtil\Validator\IsConfirmed::NOT_SAME
        );
        if (!$repeatConfirmValidator->isValid($input['repeat_password'] ?? null, $input)) {
            $this->statusMessenger->addError($this->translator->trans('Password reset failed.'), true);
            $this->validationMessenger->addValidationErrors('repeat_password', $repeatConfirmValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $user->setPassword($input['new_password']);
        $session = $request->getAttribute(SessionInterface::class);
        LoginStatusTracker::make($session, $user)->setPasswordResetActive(false);

        $this->statusMessenger->addSuccess($this->translator->trans('New password is active.'));

        return new RedirectResponse($request->getUri());
    }
}
