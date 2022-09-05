<?php

declare(strict_types=1);

namespace Gems\AuthNew;

use Gems\Site\SiteUtil;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Digits;
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
    private FlashMessagesInterface $flash;
    private array $organizations;

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
        $this->flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

        $siteUrl = $this->siteUtil->getSiteByFullUrl((string)$request->getUri());
        $this->organizations = $siteUrl ? $this->siteUtil->getNamedOrganizationsFromSiteUrl($siteUrl) : [];

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        $data = [
            'trans' => [
                'organization' => $this->translator->trans('Organization'),
                'username' => $this->translator->trans('Username'),
                'password' => $this->translator->trans('Password'),
                'login' => $this->translator->trans('Login'),
            ],
            'organizations' => $this->organizations,
            'input' => $this->flash->getFlash('login_input'),
            'errors' => $this->flash->getFlash('login_errors'),
        ];

        return new HtmlResponse($this->template->render('gems::login', $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        $input = $request->getParsedBody();

        $organizationValidation = new ValidatorChain();
        $organizationValidation->attach(new NotEmpty());
        $organizationValidation->attach(new Digits());
        $organizationValidation->attach(new InArray([
            'haystack' => array_keys($this->organizations),
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
            (int)$input['organization'],
            $input['username'],
            $input['password'],
            $request->getServerParams()['REMOTE_ADDR'] ?? null,
        );

        if (!$result->isValid()) {
            $messages = $result->getMessages() ?: [$this->translator->trans('The provided credentials are invalid')];
            return $this->redirectBack($request, implode(PHP_EOL, $messages));
        }

        return AuthenticationMiddleware::redirectToIntended($session, $this->urlHelper);
    }

    private function redirectBack(ServerRequestInterface $request, string $error): RedirectResponse
    {
        $input = $request->getParsedBody();

        $this->flash->flash('login_input', [
            'organization' => $input['organization'] ?? null,
            'username' => $input['username'] ?? null,
        ]);

        $this->flash->flash('login_errors', [$error]);

        return new RedirectResponse($request->getUri());
    }
}
