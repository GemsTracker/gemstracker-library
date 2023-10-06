<?php

namespace Gems\Snippets\Tracker\Fields;

use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Generic\PrevNextButtonRowSnippetAbstract;
use Gems\Tracker;
use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Field\FieldInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class FieldsButtonRowSnippet extends PrevNextButtonRowSnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MenuSnippetHelper $menuHelper,
        protected Tracker $tracker,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $menuHelper);
    }

    protected function getCurrentFieldKey(): string
    {
        $urlAttributes = $this->requestInfo->getRequestMatchedParams();
        $fieldId = null;
        if (isset($urlAttributes[\Gems\Model::FIELD_ID])) {
            $fieldId = $urlAttributes[\Gems\Model::FIELD_ID];
        }
        $fieldType = null;
        if (isset($urlAttributes['sub'])) {
            $fieldType = $urlAttributes['sub'];
        }

        return FieldsDefinition::makeKey($fieldType, $fieldId);
    }

    protected function getFieldsDefinition(): FieldsDefinition
    {
        $urlAttributes = $this->requestInfo->getRequestMatchedParams();
        $trackId = null;
        if (isset($urlAttributes['trackId'])) {
            $trackId = $urlAttributes['trackId'];
        }
        $trackEngine = $this->tracker->getTrackEngine($trackId);
        return $trackEngine->getFieldsDefinition();
    }

    protected function getFields(): array
    {
        $fieldsDefinition = $this->getFieldsDefinition();
        return $fieldsDefinition->getFields();
    }

    protected function getNextUrl(): ?string
    {
        $fields = $this->getFields();
        $fieldKeys = array_keys($fields);
        $currentIndex = array_search($this->getCurrentFieldKey(), $fieldKeys);

        if (isset($fieldKeys[$currentIndex+1])) {
            $nextField = $fields[$fieldKeys[$currentIndex+1]];
            $params = $this->requestInfo->getRequestMatchedParams();
            $params['sub'] = $nextField->getFieldSub();
            $params[\Gems\Model::FIELD_ID] = $nextField->getFieldId();
            return $this->menuHelper->getRouteUrl($this->requestInfo->getRouteName(), $params);
        }

        return null;
    }

    protected function getPreviousUrl(): ?string
    {
        $fields = $this->getFields();
        $fieldKeys = array_keys($fields);
        $currentIndex = array_search($this->getCurrentFieldKey(), $fieldKeys);

        if (isset($fieldKeys[$currentIndex-1])) {
            $nextField = $fields[$fieldKeys[$currentIndex-1]];
            $params = $this->requestInfo->getRequestMatchedParams();
            $params['sub'] = $nextField->getFieldSub();
            $params[\Gems\Model::FIELD_ID] = $nextField->getFieldId();
            return $this->menuHelper->getRouteUrl($this->requestInfo->getRouteName(), $params);
        }

        return null;
    }
}