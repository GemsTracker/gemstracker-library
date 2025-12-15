<?php

namespace Gems\Snippets\Respondent;

use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Vue\CreateEditSnippet;
use Gems\Tracker;
use Mezzio\Helper\UrlHelper;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class TokenEmailSnippet extends CreateEditSnippet
{
    protected ?string $afterSaveUrl = null;

    protected string $tag = 'token-mail-form';

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        LayoutSettings $layoutSettings,
        Locale $locale,
        MenuSnippetHelper $menuSnippetHelper,
        UrlHelper $urlHelper,
        array $config,
        protected Tracker $tracker,
        protected TranslatorInterface $translator,
        protected StatusMessengerInterface $messenger,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $layoutSettings, $locale, $menuSnippetHelper, $urlHelper, $config);
    }

    public function getRedirectRoute(): ?string
    {
        return $this->afterSaveUrl;
    }

    protected function gotoShowToken(): void
    {
        $this->afterSaveUrl = $this->menuSnippetHelper->getRouteUrl('respondent.tracks.token.show', $this->requestInfo->getRequestMatchedParams());
    }

    public function hasHtmlOutput(): bool
    {
        $params = $this->requestInfo->getRequestMatchedParams();
        if (!isset($params[MetaModelInterface::REQUEST_ID])) {
            throw new \Exception('No Token ID');
        }

        $tokenId = $params[MetaModelInterface::REQUEST_ID];
        $token = $this->tracker->getToken($tokenId);

        $messages = [];

        if (!$token->getReceptionCode()->isSuccess()) {
            $messages[] = $this->translator->_('This token cannot be sent. It is not valid');
        }

        if ($token->getSurvey()->isTakenByStaff()) {
            $messages[] = $this->translator->_('This token cannot be sent. It is intended for Staff');
        }

        if ($token->isNotYetValid()) {
            $messages[] = $this->translator->_('This token cannot be sent. It is not yet valid');
        }

        if ($token->isExpired()) {
            $messages[] = $this->translator->_('This token is expired');
        }

        if ($token->isCompleted()) {
            $messages[] = $this->translator->_('This token has already been completed');
        }

        if ($token->getEmail() === null) {
            $messages[] = $this->translator->_('Respondent does not have an E-mail address');
            if ($token->hasRelation() && $token->getRelation()->getEmail() === null) {
                $messages[] = $this->translator->_('Respondent relation does not have an E-mail address');
            }
        }

        if ($token->hasRelation() && !$token->getRelation()->isMailable()) {
            $messages[] = $this->translator->_('Respondent relation cannot be contacted');
        }

        if (!$token->getRespondent()->isMailable()) {
            $messages[] = $this->translator->_('Respondent relation cannot be contacted');
        }

        if (! ($messages || $token->isMailable())) {
            $messages[] = $this->translator->_('This token cannot be E-mailed');
        }

        if ($messages) {
            foreach ($messages as $message) {
                $this->messenger->addError($message);
            }
            $this->gotoShowToken();
            return false;
        }

        return true;
    }
}
