<?php

declare(strict_types=1);

namespace Gems\AuthNew\Elsewhere;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationService;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\AuthTfa\OtpMethodBuilder;
use Gems\AuthTfa\SendDecorator\SendsOtpCodeInterface;
use Gems\AuthTfa\TfaService;
use Gems\User\User;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Digits;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorChain;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TfaLoginHandler implements RequestHandlerInterface
{
    private FlashMessagesInterface $flash;
    private AuthenticationService $authenticationService;
    private TfaService $tfaService;
    private User $user;

    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly TranslatorInterface $translator,
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
        $this->tfaService = new TfaService($session, $this->authenticationService, $request, $this->otpMethodBuilder);

        if ($this->tfaService->isLoggedIn($this->user) || !$this->tfaService->requiresAuthentication($this->user)) {
            return AuthenticationMiddleware::redirectToIntended($session, $this->urlHelper);
        }

        if ($request->getMethod() === 'POST') {
            if (isset($request->getParsedBody()['resend'])) {
                $otpMethod = $this->tfaService->getOtpMethod();
                if ($otpMethod instanceof SendsOtpCodeInterface) {
                    try {
                        $otpMethod->sendCode();
                        $session->set('tfa_login_last_send', time());

                        $this->flash->flash('tfa_login_info_messages', [$otpMethod->getSentFeedbackMessage()]);
                    } catch (\Gems\Exception $e) {
                        $this->flash->flash('tfa_login_errors', [$e->getMessage()]);
                    }

                    return new RedirectResponse($request->getUri());
                }
            }

            return $this->handlePost($request);
        }

        $messages = $this->flash->getFlash('tfa_login_info_messages') ?: [];
        $errors = $this->flash->getFlash('tfa_login_errors') ?: [];

        $otpMethod = $this->tfaService->getOtpMethod();
        if ($otpMethod instanceof SendsOtpCodeInterface) {
            $lastSend = $session->get('tfa_login_last_send');
            if ($lastSend === null || time() - $lastSend > $otpMethod->getCodeValidSeconds()) {
                try {
                    $otpMethod->sendCode();
                    $session->set('tfa_login_last_send', time());
                    $messages[] = $otpMethod->getSentFeedbackMessage();
                } catch (\Gems\Exception $e) {
                    $errors[] = $e->getMessage();
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
            'errors' => $errors,
            'info_messages' => $messages,
            'sendable' => $otpMethod instanceof SendsOtpCodeInterface,
        ];

        return new HtmlResponse($this->template->render('gems::tfa/login', $data));
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

        return AuthenticationMiddleware::redirectToIntended($session, $this->urlHelper);
    }

    private function redirectBack(ServerRequestInterface $request, string $error): RedirectResponse
    {
        $this->flash->flash('tfa_login_errors', [$error]);

        return new RedirectResponse($request->getUri());
    }
}
