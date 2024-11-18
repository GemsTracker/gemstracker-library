<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Ask;

use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\OrganizationRepository;
use Gems\Tracker\Token;
use Gems\Tracker\Token\TokenHelpers;
use Gems\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\MessageableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @since      Class available since version 1.0
 */
class ToSurveyAskSnippet extends MessageableSnippetAbstract
{
    protected ?User $currentUser;

    /**
     * Required, the current token, possibly already answered
     */
    protected Token $token;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        CurrentUserRepository $currentUserRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
        protected readonly ServerRequestInterface $request,
        protected readonly SessionInterface $session,
        protected readonly TokenHelpers $tokenHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    protected function checkReturnUrl(string $url): ? string
    {
        if (! $this->organizationRepository->isAllowedUrl($url)) {
            return null;
        }

        // Fix for ask index redirect to forward not set in HTTP_REFERER in RedirectLoop
        $urlWithoutQueryParams = strstr($url, '?', true) ?: $url;
        $askIndexUrl = $this->menuSnippetHelper->getRouteUrl('ask.index');

        // If there is no previous url or the url was the index url, then set the return to nothing
        if ($this->token instanceof Token &&
            ((! $url) || str_starts_with($askIndexUrl, $urlWithoutQueryParams))
            ) {

            return null;
//            $forwardUrl = $this->menuSnippetHelper->getRouteUrl('ask.forward', [
//                MetaModelInterface::REQUEST_ID => $this->token->getTokenId(),
//            ]);
//            return $forwardUrl;
        }

        return $url;
    }

    public function getResponse(): ?ResponseInterface
    {
        if ($this->token->isCompleted()) {
            $this->messenger->addMessages([$this->_('Thank you for completing the survey')]);
            $url = $this->menuSnippetHelper->getRelatedRouteUrl('index');

            return new RedirectResponse($url);
        }

        if ($this->currentUser && $this->currentUser->isLogoutOnSurvey()) {
            $this->session->regenerate();
            $this->session->clear();
        }

        $returnUrl = $this->checkReturnUrl($this->tokenHelper->getReturnUrl($this->request, $this->token));
        $url  = $this->token->getUrl(
            $this->token->getRespondentLanguage(),
            $this->currentUser instanceof User ? $this->currentUser->getUserId() : $this->token->getRespondentId(),
            $returnUrl
        );

        return new RedirectResponse($url);
    }

    public function hasHtmlOutput(): bool
    {
        return false;
    }
}