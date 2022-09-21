<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\AuthNew\Adapter\EmbedAuthentication;
use Gems\AuthNew\Adapter\EmbedAuthenticationResult;
use Gems\AuthNew\Adapter\EmbedIdentity;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\Repository\RespondentRepository;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmbedLoginHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly UserLoader $userLoader,
        private readonly RespondentRepository $respondentRepository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionInterface::class);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);

        $input = ($request->getMethod() === 'POST') ? $request->getParsedBody() : $request->getQueryParams();

        $result = null;

        // TODO: org should be an existing organization?

        if (!empty($input['epd'])
            && !empty($input['key'])
            && !empty($input['usr'])
            && !empty($input['pid'])
            && !empty($input['org'])
            && ctype_digit($input['org'])
        ) {
            /** @var EmbedAuthenticationResult $result */
            $result = $authenticationService->authenticate(new EmbedAuthentication(
                $this->userLoader,
                $this->respondentRepository,
                $input['epd'],
                $input['key'],
                $input['usr'],
                $input['pid'],
                (int)$input['org'],
            ));
        }

        if ($result && $result->isValid()) {
            /** @var EmbedIdentity $identity */
            $identity = $result->getIdentity();

            $embeddedUserData = $result->systemUser->getEmbedderData();
            $redirector = $embeddedUserData->getRedirector();

            //if ($redirector instanceof RedirectAbstract) {
            //    $redirector->answerRegistryRequest('request', $this->getRequest());
            //}

            $url = $redirector?->getRedirectUrl(
                $this->urlHelper,
                $result->systemUser,
                $result->deferredUser,
                $identity->getPatientId(),
                [$identity->getOrganizationId()],
            );

            return new RedirectResponse($url);
        } else {
            throw new \Gems\Exception($this->translator->trans("Unable to authenticate"));
        }
    }
}
