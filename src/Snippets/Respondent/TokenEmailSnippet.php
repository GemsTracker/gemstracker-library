<?php

namespace Gems\Snippets\Respondent;

use Gems\Html;
use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Vue\CreateEditSnippet;
use Gems\Tracker;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Model;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\Message\StatusMessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class TokenEmailSnippet extends CreateEditSnippet
{

    protected ?string $afterSaveUrl = null;
    
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        LayoutSettings $layoutSettings,
        TemplateRendererInterface $templateRenderer,
        Locale $locale,
        UrlHelper $urlHelper,
        array $config,
        protected Tracker $tracker,
        protected Translator $translator,
        protected StatusMessengerInterface $messenger,
        protected MenuSnippetHelper $menuSnippetHelper,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $layoutSettings, $templateRenderer, $locale, $urlHelper, $config);
        $this->layoutSettings = $layoutSettings;
        $this->templateRenderer = $templateRenderer;
        $this->locale = $locale;
    }

    public function getRedirectRoute(): ?string
    {
        return $this->afterSaveUrl;
    }

    protected function gotoShowToken(): void
    {
        $this->afterSaveUrl = $this->menuSnippetHelper->getRouteUrl('respondent.tracks.show', $this->requestInfo->getRequestMatchedParams());
    }

    public function hasHtmlOutput(): bool
    {
        $params = $this->requestInfo->getRequestMatchedParams();
        if (!isset($params[Model::REQUEST_ID])) {
            throw new \Exception('No Token ID');
        }

        $tokenId = $params[Model::REQUEST_ID];
        $token = $this->tracker->getToken($tokenId);

        $message = null;

        if (!$token->getReceptionCode()->isSuccess()) {
            $message = $this->translator->_('This token cannot be sent. It is not valid');
        }

        if ($token->getSurvey()->isTakenByStaff()) {
            $message = $this->translator->_('This token cannot be sent. It is intended for Staff');
        }

        if ($token->isNotYetValid()) {
            $message = $this->translator->_('This token cannot be sent. It is not yet valid');
        }

        if ($token->isExpired()) {
            $message = $this->translator->_('This token is expired');
        }

        if ($token->isCompleted()) {
            $message = $this->translator->_('This token has already been completed');
        }

        if ($token->getEmail() === null) {
            $message = $this->translator->_('Respondent does not have an E-mail address');
            if ($token->hasRelation() && $token->getRelation()->getEmail() === null) {
                $message = $this->translator->_('Respondent relation does not have an E-mail address');
            }
        }

        if ($token->hasRelation() && !$token->getRelation()->isMailable()) {
            $message = $this->translator->_('Respondent relation cannot be contacted');
        }

        if (!$token->getRespondent()->isMailable()) {
            $message = $this->translator->_('Respondent relation cannot be contacted');
        }

        if (!$token->isMailable()) {
            $message = $this->translator->_('This token cannot be E-mailed');
        }

        if ($message !== null) {
            $this->messenger->addError($message);
            $this->gotoShowToken();
            return false;
        }

        return true;
    }
}
