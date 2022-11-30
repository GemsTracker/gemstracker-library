<?php

namespace Gems\Snippets\Generic;

abstract class PrevNextButtonRowSnippetAbstract extends CurrentButtonRowSnippet
{
    protected function addButtons(): array
    {
        $buttons = [];

        $prevUrl = $this->getPreviousUrl();

        $buttons['prev'] = [
            'label' => \Zalt\Html\Html::raw($this->_('&lt; Previous')),
            'url' => $prevUrl,
            'disabled' => $prevUrl === null,
        ];

        $buttons = array_merge($buttons, parent::addButtons());

        $nextUrl = $this->getNextUrl();

        $buttons['next'] = [
            'label' => \Zalt\Html\Html::raw($this->_('Next &gt;')),
            'url' => $nextUrl,
            'disabled' => $nextUrl === null,
        ];

        return $buttons;
    }

    abstract protected function getNextUrl(): ?string;

    abstract protected function getPreviousUrl(): ?string;
}