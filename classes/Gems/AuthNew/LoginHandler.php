<?php

declare(strict_types=1);

namespace Gems\AuthNew;

use Gems\Site\SiteUtil;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\InArray;
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

class LoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template,
        private readonly TranslatorInterface $translator,
        private readonly SiteUtil $siteUtil,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        /** @var FlashMessagesInterface $flashMessenger */
        $flashMessenger = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $siteUrl = $this->siteUtil->getSiteByFullUrl((string)$request->getUri());
        $organizations = $siteUrl ? $this->siteUtil->getNamedOrganizationsFromSiteUrl($siteUrl) : [];

        $data = [
            'trans' => [
                'organization' => $this->translator->trans('Organization'),
                'username' => $this->translator->trans('Username'),
                'password' => $this->translator->trans('Password'),
                'login' => $this->translator->trans('Login'),
            ],
            'organizations' => $organizations,
            'input' => $flashMessenger->getFlash('login_input'),
            'errors' => $flashMessenger->getFlash('login_errors'),
        ];

        return new HtmlResponse($this->template->render('gems::login', $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        $input = $request->getParsedBody();

        $siteUrl = $this->siteUtil->getSiteByFullUrl((string)$request->getUri());
        $organizations = $siteUrl ? $this->siteUtil->getNamedOrganizationsFromSiteUrl($siteUrl) : [];

        $organizationValidation = new ValidatorChain();
        $organizationValidation->attach(new NotEmpty());
        $organizationValidation->attach(new InArray([
            'haystack' => array_keys($organizations),
        ]));

        $notEmptyValidation = new ValidatorChain();
        $notEmptyValidation->attach(new NotEmpty());

        if (
            !$organizationValidation->isValid($input['organization'] ?? null)
            || !$notEmptyValidation->isValid($input['username'] ?? null)
            || !$notEmptyValidation->isValid($input['password'] ?? null)
        ) {
            return $this->redirectBack($request, $this->translator->trans('Make sure you fill in all fields'));
        }

        $result = $authenticationService->routedAuthenticate(
            $input['organization'],
            $input['username'],
            $input['password'],
        );

        if (!$result->isValid()) {
            return $this->redirectBack($request, $this->translator->trans('The provided credentials are invalid'));
        }

        return new RedirectResponse($this->urlHelper->generate('track-builder.source.index'));
    }

    private function redirectBack(ServerRequestInterface $request, string $error): RedirectResponse
    {
        /** @var FlashMessagesInterface $flashMessenger */
        $flashMessenger = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $flashMessenger->flash('login_input', [
            'organization' => $input['organization'] ?? null,
            'username' => $input['username'] ?? null,
        ]);

        $flashMessenger->flash('login_errors', [$error]);

        return new RedirectResponse($request->getUri());
    }
}
