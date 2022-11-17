<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationService;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\AuthTfa\SendDecorator\SendsOtpCodeInterface;
use Gems\AuthTfa\TfaService;
use Gems\DecoratedFlashMessagesInterface;
use Gems\Layout\LayoutRenderer;
use Gems\User\User;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Digits;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorChain;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TfaLoginHandler implements RequestHandlerInterface
{
    private DecoratedFlashMessagesInterface $flash;
    private AuthenticationService $authenticationService;
    private TfaService $tfaService;
    private User $user;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly OtpMethodBuilder $otpMethodBuilder,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionInterface::class);
        $this->authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $this->user = $this->authenticationService->getLoggedInUser();
        $this->tfaService = new TfaService($session, $this->authenticationService, $this->otpMethodBuilder);

        if ($this->tfaService->isLoggedIn($this->user) || !$this->tfaService->requiresAuthentication($this->user, $request)) {
            return AuthenticationMiddleware::redirectToIntended($this->authenticationService, $session, $this->urlHelper);
        }

        if ($request->getMethod() === 'POST') {
            if (isset($request->getParsedBody()['resend'])) {
                $otpMethod = $this->tfaService->getOtpMethod();
                if ($otpMethod instanceof SendsOtpCodeInterface) {
                    try {
                        $otpMethod->sendCode();
                        $session->set('tfa_login_last_send', time());

                        $this->flash->flashInfo($otpMethod->getSentFeedbackMessage());
                    } catch (\Gems\Exception $e) {
                        $this->flash->flashError($e->getMessage());
                    }

                    return new RedirectResponse($request->getUri());
                }
            }

            return $this->handlePost($request);
        }

        $otpMethod = $this->tfaService->getOtpMethod();
        if ($otpMethod instanceof SendsOtpCodeInterface) {
            $lastSend = $session->get('tfa_login_last_send');
            if ($lastSend === null || time() - $lastSend > $otpMethod->getCodeValidSeconds()) {
                try {
                    $otpMethod->sendCode();
                    $session->set('tfa_login_last_send', time());
                    $this->flash->appendInfo($otpMethod->getSentFeedbackMessage());
                } catch (\Gems\Exception $e) {
                    $this->flash->appendError($e->getMessage());
                }
            }
        }

        $data = [
            'trans' => [
                'code_input_label' => $this->translator->trans('Enter authenticator code'),
                'code_input_description' => $otpMethod->getCodeInputDescription(),
                'continue' => $this->translator->trans('Continue'),
                'resend_code' => $this->translator->trans('Resend code'),
            ],
            'code_min_length' => $otpMethod->getMinLength(),
            'code_max_length' => $otpMethod->getMaxLength(),
            'sendable' => $otpMethod instanceof SendsOtpCodeInterface,
        ];

        return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::tfa/login', $request, $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionInterface::class);

        $input = $request->getParsedBody();

        $tfaValidation = new ValidatorChain();
        $tfaValidation->attach(new NotEmpty());
        $tfaValidation->attach(new Digits());

        if (!$tfaValidation->isValid($input['tfa_code'] ?? null)) {
            return $this->redirectBack($request, $this->translator->trans('Please provide a valid TFA code'));
        }

        $otpMethod = $this->tfaService->getOtpMethod();
        if (!$otpMethod->canVerifyOtp($this->user)) {
            return $this->redirectBack($request, $this->translator->trans(
                'Maximum number of OTP attempts reached, please try again within a few minutes'
            ));
        }

        if (!$this->tfaService->verify($input['tfa_code'])) {
            $otpMethod->hitVerifyOtp($this->user);
            return $this->redirectBack($request, $this->translator->trans('The provided TFA code is invalid'));
        }

        $session->unset('tfa_login_last_send');

        return AuthenticationMiddleware::redirectToIntended($this->authenticationService, $session, $this->urlHelper);
    }

    private function redirectBack(ServerRequestInterface $request, string $error): RedirectResponse
    {
        $this->flash->flashError($error);

        return new RedirectResponse($request->getUri());
    }
}
