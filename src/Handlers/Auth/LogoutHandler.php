<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\Audit\AuditLog;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\AuthTfa\TfaService;
use Gems\Middleware\FlashMessageMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Message\MessageStatus;
use Zalt\Message\StatusMessengerInterface;

class LogoutHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly OtpMethodBuilder $otpMethodBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly AuditLog $auditLog,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_WITHOUT_TFA_ATTRIBUTE);
        if ($user) {
            $this->auditLog->registerUserRequest($request, $user,
                [sprintf('%s logged out', $user->getLoginName())]
            );
        }

        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $tfaService = new TfaService($session, $authenticationService, $this->otpMethodBuilder);
        /** @var StatusMessengerInterface|null $statusMessenger */
        $statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        $messages = [];
        if ($statusMessenger) {
            $messages = $this->getMessages($statusMessenger);
        }

        $tfaService->logout();
        $authenticationService->logout();

        $session->clear();

        if ($statusMessenger) {
            $this->putMessages($statusMessenger, $messages);
        }

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }

    /**
     * Get current flash messages from the session.
     */
    private function getMessages(StatusMessengerInterface $statusMessenger): array
    {
        $dangerMessages = $statusMessenger->getMessages(MessageStatus::Danger);
        $infoMessages = $statusMessenger->getMessages(MessageStatus::Info);
        $warningMessages = $statusMessenger->getMessages(MessageStatus::Warning);
        $successMessages = $statusMessenger->getMessages(MessageStatus::Success);

        return [$dangerMessages, $infoMessages, $warningMessages, $successMessages];
    }

    /**
     * Put flash messages back into the session.
     */
    private function putMessages(StatusMessengerInterface $statusMessenger, array $messages): void
    {
        [$dangerMessages, $infoMessages, $warningMessages, $successMessages] = $messages;

        if (!empty($dangerMessages)) {
            $statusMessenger->addMessages($dangerMessages, MessageStatus::Danger);
        }
        if (!empty($infoMessages)) {
            $statusMessenger->addMessages($infoMessages, MessageStatus::Info);
        }
        if (!empty($warningMessages)) {
            $statusMessenger->addMessages($warningMessages, MessageStatus::Warning);
        }
        if (!empty($successMessages)) {
            $statusMessenger->addMessages($successMessages, MessageStatus::Success);
        }
    }
}
