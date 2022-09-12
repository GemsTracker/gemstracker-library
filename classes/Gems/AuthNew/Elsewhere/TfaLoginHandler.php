<?php

declare(strict_types=1);

namespace Gems\AuthNew\Elsewhere;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\AuthNew\AuthenticationService;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\AuthNew\TfaService;
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

    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly TranslatorInterface $translator,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);
        $session = $request->getAttribute(SessionInterface::class);
        $this->authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $user = $this->authenticationService->getLoggedInUser();
        $this->tfaService = new TfaService($session, $this->authenticationService, $request);

        if ($this->tfaService->isLoggedIn($user) || !$this->tfaService->requiresAuthentication($user)) {
            return AuthenticationMiddleware::redirectToIntended($session, $this->urlHelper);
        }

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        $data = [
            'trans' => [
                'tfa_code' => $this->translator->trans('TFA code'),
                'continue' => $this->translator->trans('Continue'),
            ],
            'errors' => $this->flash->getFlash('tfa_login_errors'),
        ];

        return new HtmlResponse($this->template->render('gems::tfa/login', $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);

        $input = $request->getParsedBody();

        $tfaValidation = new ValidatorChain();
        $tfaValidation->attach(new NotEmpty());
        $tfaValidation->attach(new Digits());

        if (!$tfaValidation->isValid($input['tfa_code'] ?? null)) {
            return $this->redirectBack($request, $this->translator->trans('Please provide a valid TFA code'));
        }

        if (!$this->tfaService->verify($input['tfa_code'])) {
            return $this->redirectBack($request, $this->translator->trans('The provided TFA code is invalid'));
        }

        return AuthenticationMiddleware::redirectToIntended($session, $this->urlHelper);
    }

    private function redirectBack(ServerRequestInterface $request, string $error): RedirectResponse
    {
        $this->flash->flash('tfa_login_errors', [$error]);

        return new RedirectResponse($request->getUri());
    }
}
