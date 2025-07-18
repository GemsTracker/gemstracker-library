<?php

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;
use Gems\Exception\ConsentCreateException;
use Gems\Translate\CachedDbTranslationRepository;
use Gems\Util\ConsentCode;
use Zalt\Base\TranslatorInterface;

class ConsentRepository
{
    protected array $cacheTags = [
        'consents',
    ];

    protected array $consentConfig = [];

    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
        protected readonly TranslatorInterface $translator,
        protected readonly CachedDbTranslationRepository $dbTranslationRepository,
        readonly array $config
    )
    {
        if (isset($config['consents'])) {
            $this->consentConfig = $config['consents'];
        }
    }

    public function getConsentFromConsentData(array $data): ConsentCode
    {
        if (!array_key_exists('gco_description', $data) || !array_key_exists('gco_code', $data)) {
            throw new ConsentCreateException('Consent data missing');
        }

        $order = $data['gco_order'] ?? null;

        return new ConsentCode(
            $data['gco_description'],
            $data['gco_code'],
            $order,
            $this->getConsentRejected(),
        );
    }

    public function getConsentFromDescription(string $description): ConsentCode|null
    {
        $userConsents = $this->getUserConsents();
        foreach($userConsents as $userConsent) {
            if ($userConsent['gco_description'] === $description) {
                return $this->getConsentFromConsentData($userConsent);
            }
        }

        return null;
    }

    /**
     * Retrieve the array of possible consentCODEs to use for responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return array Default consent codes are 'do not use' and 'consent given'
     */
    public function getConsentTypes(): array
    {
        if (isset($this->consentConfig['consentTypes'])) {
            $consentTypes = explode('|', $this->consentConfig['consentTypes']);
        } else {
            $consentTypes = ['do not use', 'consent given'];
        }

        return array_combine($consentTypes, $consentTypes);
    }

    /**
     * Get the default user consent
     *
     * This is de consent description from gems__consents, not the consentCODE
     *
     * @return string
     */
    public function getDefaultConsent(): string
    {
        if (isset($this->consentConfig['consentDefault'])) {
            return $this->consentConfig['consentDefault'];
        }

        return $this->getConsentUnknown();
    }

    /**
     * @return array<string, string> Untranslated value => Translated value
     */
    public function getConsents(): array
    {
        return array_column($this->getUserConsents(), 'gco_description', 'untranslated_description');
    }

    /**
     * Retrieve the consentCODE to use for rejected responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return string Default value is 'do not use'
     */
    public function getConsentRejected(): string
    {
        if (isset($this->consentConfig['consentRejected'])) {
            return $this->consentConfig['consentRejected'];
        }

        return 'do not use';
    }

    /**
     * Get the code to use when revoking a consent
     *
     * @return string
     */
    public function getConsentRevoked(): string
    {
        if (isset($this->consentConfig['consentRevoked'])) {
            return $this->consentConfig['consentRevoked'];
        }

        return 'No';
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

    /**
     * @return array<int, array> Array containing: untranslated_description, gco_description, gco_code, gco_order
     */
    public function getUserConsents(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__consents');
        $select->columns(['gco_description AS untranslated_description', 'gco_description', 'gco_code', 'gco_order'])
            ->order(['gco_order']);

        $result = $this->cachedResultFetcher->fetchAll(__FUNCTION__, $select, null, $this->cacheTags);
        return $this->dbTranslationRepository->translateTable(__FUNCTION__, 'gems__consents', 'gco_description', $result);
    }

    public function getUserConsentOptions(): array
    {
        $userConsents = $this->getUserConsents();
        $consentValues = array_column($userConsents, 'gco_description', 'untranslated_description');
        if ($this->config['model']['translateDatabaseFields'] ?? false) {
            return $consentValues;
        }

        // If NOT tranlated from the database, the use normal translations
        $output       = [];
        foreach ($consentValues as $key => $value) {
            $output[$key] = $this->translator->_($value);
        }
        return $output;
    }
}