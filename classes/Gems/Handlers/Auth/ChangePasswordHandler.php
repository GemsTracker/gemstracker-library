<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AccessLog\AccesslogRepository;
use Gems\AuthNew\Adapter\GemsTrackerAuthentication;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\LoginStatusTracker;
use Gems\DecoratedFlashMessagesInterface;
use Gems\Layout\LayoutRenderer;
use Gems\User\PasswordChecker;
use Gems\User\User;
use Laminas\Db\Adapter\Adapter;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChangePasswordHandler implements RequestHandlerInterface
{
    private DecoratedFlashMessagesInterface $flash;

    public function __construct(
        private readonly Adapter $db,
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly UrlHelper $urlHelper,
        private readonly AccesslogRepository $accesslogRepository,
        private readonly PasswordChecker $passwordChecker,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);
        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        /** @var User $user */
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

        if (
            !$user->isActive()
            || !$user->canResetPassword()
            || !$user->isAllowedIpForLogin($request->getServerParams()['REMOTE_ADDR'] ?? null)
        ) {
            $this->flash->appendError($this->translator->trans('You cannot reset your password.'));
            if ($request->getMethod() === 'POST') {
                return new RedirectResponse($request->getUri());
            }
        }

        if ($request->getMethod() !== 'POST') {
            $data = [
                'ask_old' => true,
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
            $this->flash->flashValidationErrors('old_password', [$this->translator->trans('Wrong password.')]);
            return new RedirectResponse($request->getUri());
        }

        $newPasswordValidator = new \Gems\User\Validate\NewPasswordValidator($user, $this->passwordChecker);
        if (!$newPasswordValidator->isValid($input['new_password'] ?? null)) {
            $this->flash->flashValidationErrors('new_password', $newPasswordValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $repeatConfirmValidator = new \MUtil\Validate\IsConfirmed('new_password', $this->translator->trans('New password'));
        $repeatConfirmValidator->setMessage(
            $this->translator->trans('Must be the same as %fieldDescription%.'),
            \MUtil\Validate\IsConfirmed::NOT_SAME
        );
        if (!$repeatConfirmValidator->isValid($input['repeat_password'] ?? null, $input)) {
            $this->flash->flashValidationErrors('repeat_password', $repeatConfirmValidator->getMessages());
            return new RedirectResponse($request->getUri());
        }

        $user->setPassword($input['new_password']);
        LoginStatusTracker::make($session, $user)->setPasswordResetActive(false);

        $this->flash->flashSuccess($this->translator->trans('New password is active.'));

        return new RedirectResponse($request->getUri());
    }
}
