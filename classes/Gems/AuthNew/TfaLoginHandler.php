<?php

declare(strict_types=1);

namespace Gems\AuthNew;

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
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $user = $authenticationService->getLoggedInUser();

        $tfaService = new TfaService($session, $authenticationService, $request);

        $input = $request->getParsedBody();

        $tfaValidation = new ValidatorChain();
        $tfaValidation->attach(new NotEmpty());
        $tfaValidation->attach(new Digits());

        if (!$tfaValidation->isValid($input['tfa_code'] ?? null)) {
            return $this->redirectBack($request, $this->translator->trans('Please provide the 6-digit TFA code'));
        }

        $result = $tfaService->authenticate(new TotpTfa($user, $input['tfa_code']));

        if (!$result->isValid()) {
            return $this->redirectBack($request, $this->translator->trans('The provided TFA code is invalid'));
        }

        return new RedirectResponse($this->urlHelper->generate('track-builder.source.index')); // TODO: Which route?
    }

    private function redirectBack(ServerRequestInterface $request, string $error): RedirectResponse
    {
        $this->flash->flash('tfa_login_errors', [$error]);

        return new RedirectResponse($request->getUri());
    }
}
