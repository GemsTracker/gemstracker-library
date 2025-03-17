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
            'Add surveys for' => $this->_('Add surveys for'),
            'Added by' => $this->_('Added by'),
            'Birthdate' => $this->_('Birthdate'),
            'Cancel' => $this->_('Cancel'),
            'Changed' => $this->_('Changed'),
            'Changed by' => $this->_('Changed by'),
            'Comment' => $this->_('Comment'),
            'Completed' => $this->_('Completed'),
            'Consent' => $this->_('Consent'),
            'Copy token to clipboard' => $this->_('Copy token to clipboard'),
            'Correct answers' => $this->_('Correct answers'),
            'Delete track!' => $this->_('Delete track!'),
            'Description' => $this->_('Description'),
            'Details' => $this->_('Details'),
            'Edit' => $this->_('Edit'),
            'Female' => $this->_('Female'),
            'Gender' => $this->_('Gender'),
            'Location' => $this->_('Location'),
            'Male' => $this->_('Male'),
            'Missed' => $this->_('Missed'),
            'No' => $this->_('No'),
            'Open until' => $this->_('Open until'),
            'Organization' => $this->_('Organization'),
            'Respondents' => $this->_('Respondents'),
            'Patients' => $this->_('Patients'),
            'Phone number' => $this->_('Phone number'),
            'Progress' => $this->_('Progress'),
            'Restore track!' => $this->_('Restore track!'),
            'Row' => $this->_('Row'),
            'Save' => $this->_('Save'),
            'Show' => $this->_('Show'),
            'Show patient' => $this->_('Show patient'),
            'Show token' => $this->_('Show token'),
            'Staff' => $this->_('Staff'),
            'Start' => $this->_('Start'),
            'Start date' => $this->_('Start date'),
            'Survey' => $this->_('Survey'),
            'Track' => $this->_('Track'),
            'Tracks' => $this->_('Tracks'),
            'Track info' => $this->_('Track info'),
            'Unknown' => $this->_('Unknown'),
            'Variables' => $this->_('Variables'),
            'Yes' => $this->_('Yes'),
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