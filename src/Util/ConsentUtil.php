<?php

namespace Gems\Util;

use Gems\Project\ProjectSettings;
use Zalt\Loader\ProjectOverloader;

class ConsentUtil
{
    public function __construct(protected ProjectSettings $project, protected ProjectOverloader $overloader)
    {}

    /**
     * Returns a single consent code object.
     *
     * @param string $description
     * @return \Gems\Util\ConsentCode
     */
    public function getConsent(string $description): ConsentCode
    {
        static $codes = [];

        if (! isset($codes[$description])) {
            $codes[$description] = $this->overloader->create('Util\\ConsentCode', [$description]);
        }

        return $codes[$description];
    }

    /**
     * Retrieve the consentCODE to use for rejected responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return string Default value is 'do not use'
     * @throws \Gems\Exception\Coding
     */
    public function getConsentRejected(): string
    {
        if ($this->project->offsetExists('consentRejected')) {
            return $this->project->consentRejected;
        }

        return 'do not use';
    }

    /**
     * Retrieve the array of possible consentCODEs to use for responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return array Default consent codes are 'do not use' and 'consent given'
     */
    public function getConsentTypes(): array
    {
        if (isset($this->project->consentTypes)) {
            $consentTypes = explode('|', $this->project->consentTypes);
        } else {
            $consentTypes = ['do not use', 'consent given'];
        }

        return array_combine($consentTypes, $consentTypes);
    }

    /**
     * Get the code for an unknwon user consent
     *
     * This is de consent description from gems__consents, not the consentCODE
     *
     * @return string
     */
    public function getConsentUnknown(): string
    {
        return 'Unknown';
    }
}