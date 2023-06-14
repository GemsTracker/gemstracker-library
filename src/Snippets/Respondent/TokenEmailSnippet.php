<?php

namespace Gems\Snippets\Respondent;

use Gems\Html;
use Gems\Layout\LayoutSettings;
use Gems\Locale\Locale;
use Gems\Snippets\Vue\CreateEditSnippet;
use Gems\Tracker;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Model;
use MUtil\Translate\Translator;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

class TokenEmailSnippet extends CreateEditSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        LayoutSettings $layoutSettings,
        TemplateRendererInterface $templateRenderer,
        Locale $locale,
        protected Tracker $tracker,
        protected Translator $translator,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $layoutSettings, $templateRenderer, $locale);
        $this->layoutSettings = $layoutSettings;
        $this->templateRenderer = $templateRenderer;
        $this->locale = $locale;
    }

    public function getHtmlOutput()
    {
        $params = $this->requestInfo->getRequestMatchedParams();
        if (!isset($params[Model::REQUEST_ID])) {
            throw new \Exception('No Token ID');
        }

        $tokenId = $params[Model::REQUEST_ID];
        $token = $this->tracker->getToken($tokenId);



        if ($token->getSurvey()->isTakenByStaff()) {
            $message = $this->translator->_('This token is intended for Staff');
            return Html::div($message);
        }

        if ($token->isNotYetValid()) {
            $message = $this->translator->_('This token is not yet valid');
            return Html::div($message);
        }

        if ($token->isExpired()) {
            $message = $this->translator->_('This token is expired');
            return Html::div($message);
        }

        if ($token->isCompleted()) {
            $message = $this->translator->_('This token has already been completed');
            return Html::div($message);
        }

        if ($token->getEmail() === null) {
            $message = $this->translator->_('Respondent does not have an E-mail address');
            if ($token->hasRelation()) {
                $message = $this->translator->_('Respondent relation does not have an E-mail address');
            }
            return Html::div($message);
        }

        if (!$token->canBeEmailed()) {
            $message = $this->translator->_('This token cannot be E-mailed');
            return Html::div($message);
        }

        return parent::getHtmlOutput();
    }
}
