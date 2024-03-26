<?php

namespace Gems\Translate;

use Zalt\Base\TranslatorInterface;

class JavascriptTranslations
{
    protected string $locale = 'en';

    public function __construct(
        protected readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(string $locale = 'en'): array
    {
        $this->locale = $locale;

        return $this->getTranslations();
    }

    public function getTranslations(): array
    {
        return [
            ...$this->getGeneralTranslations(),
            ...$this->getRespondentTranslations(),
        ];
    }

    public function _(string $translationKey): string
    {
        return $this->translator->_($translationKey, [], null, $this->locale);
    }

    public function getGeneralTranslations(): array
    {
        return [
            'Add' => $this->_('Add'),
            'Added by' => $this->_('Added by'),
            'Cancel' => $this->_('Cancel'),
            'Edit' => $this->_('Edit'),
            'Description' => $this->_('Description'),
            'Progress' => $this->_('Progress'),
            'Row' => $this->_('Row'),
            'Show' => $this->_('Show'),
            'Start' => $this->_('Start'),
            'Start date' => $this->_('Start date'),
            'Track' => $this->_('Track'),
            'Variables' => $this->_('Variables'),
        ];
    }

    public function getRespondentTranslations(): array
    {
        return [
            'Delete patient' => $this->_('Delete patient'),
            'Undelete patient' => $this->_('Undelete patient'),
        ];
    }

}