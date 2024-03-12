<?php

declare(strict_types=1);

namespace Gems\Handlers\Auth;

use Gems\Audit\AuditLog;
use Gems\AuthNew\IpFinder;
use Gems\AuthNew\PasswordResetThrottleBuilder;
use Gems\Communication\CommunicationRepository;
use Gems\Communication\Exception;
use Gems\Layout\LayoutRenderer;
use Gems\Middleware\FlashMessageMiddleware;
use Gems\Site\SiteUtil;
use Gems\User\User;
use Gems\User\UserLoader;
use Gems\User\UserMailer;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Validator\Digits;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\ValidatorChain;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class RequestPasswordResetHandler implements RequestHandlerInterface
{
    private StatusMessengerInterface $statusMessenger;
    private array $organizations;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly SiteUtil $siteUtil,
        private readonly PasswordResetThrottleBuilder $passwordResetThrottleBuilder,
        private readonly UrlHelper $urlHelper,
        private readonly UserLoader $userLoader,
        private readonly AuditLog $auditLog,
        private readonly CommunicationRepository $communicationRepository,
        private readonly UserMailer $userMailer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->statusMessenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

        $siteUrl = $this->siteUtil->getSiteByFullUrl((string)$request->getUri());
        $this->organizations = $siteUrl ? $this->siteUtil->getNamedOrganizationsFromSiteUrl($siteUrl) : [];

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request);
        }

        $data = [
            'organizations' => $this->organizations,
        ];

        return new HtmlResponse($this->layoutRenderer->renderTemplate('gems::request-password-reset', $request, $data));
    }

    private function handlePost(ServerRequestInterface $request): ResponseInterface
    {
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
        ) {
            return $this->redirectBack($request, [$this->translator->trans('Make sure you fill in all fields')]);
        }

        $passwordResetThrottle = $this->passwordResetThrottleBuilder->buildPasswordResetThrottle(
            IpFinder::getClientIp($request),
            (int)$input['organization'],
        );

        $blockMinutes = $passwordResetThrottle->checkBlock();
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        $user = $this->userLoader->getUserOrNull($input['username'], (int)$input['organization']);

        if (
            $user !== null
            && $user->isActive()
            && $user->canResetPassword()
            && $user->isAllowedOrganization((int)$input['organization'])
            && $user->isAllowedIpForLogin(IpFinder::getClientIp($request))
        ) {
            try {
                $this->sendUserResetEMail($user);
            } catch (Exception $e) {
                $this->auditLog->registerUserRequest(
                    $request,
                    $user,
                    [$e->getMessage()],
                );
            }
        }

        $this->statusMessenger->addInfo($this->translator->trans(
            'If the entered username or e-mail is valid, we have sent you an e-mail with a reset link. Click on the link in the e-mail.'
        ));

        $passwordResetThrottle->registerAttempt();
        $blockMinutes = $passwordResetThrottle->checkBlock();
        if ($blockMinutes > 0) {
            return $this->redirectBack($request, [$this->blockMessage($blockMinutes)]);
        }

        return new RedirectResponse($this->urlHelper->generate('auth.login'));
    }

    private function blockMessage(int $minutes)
    {
        return $this->translator->plural(
            'You have attempted a password reset multiple times. Please wait a minute before trying again.',
            'You have attempted a password reset multiple times. Please wait %count% minutes before trying again.',
            $minutes
        );
    }

    private function redirectBack(ServerRequestInterface $request, array $errors): RedirectResponse
    {
        $this->statusMessenger->addErrors($errors);

        return new RedirectResponse($request->getUri());
    }

    /**
     * Send the user an e-mail with a link for password reset
     *
     * @param User $user
     * @return void
     * @throws Exception
     */
    public function sendUserResetEMail(User $user): void
    {
        $templateId = $this->communicationRepository->getResetPasswordTemplate($user->getBaseOrganization());
        if ($templateId) {
            [
                'subject' => $subjectTemplate,
                'body' => $bodyTemplate
            ] = $this->communicationRepository->getCommunicationTexts($templateId, $user->getLocale());
        } else {
            $subjectTemplate = $this->translator->trans('Password reset requested');

            // Multi line strings did not come through correctly in poEdit
            $bodyTemplate = $this->translator->trans("Dear {{greeting}},<br><br><br>A new password was requested for your <strong>{{organization}}</strong> account on the <strong>{{project}}</strong> site, please click within {{reset_in_hours}} hours on <a href=\"{{reset_url}}\">this link</a> to enter the password of your choice.<br><br><br>{{organization_signature}}<br><br><a href=\"{{reset_url}}\">{{reset_url}}</a><br>");
        }

        $this->userMailer->sendMail($user, $subjectTemplate, $bodyTemplate, true);
    }
}
