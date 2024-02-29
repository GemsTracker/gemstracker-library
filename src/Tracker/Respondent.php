<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker;

use DateTimeImmutable;
use DateTimeInterface;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model\Respondent\RespondentModel;
use Gems\Repository\ConsentRepository;
use Gems\Repository\MailRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Tracker;
use Gems\Translate\GenderTranslation;
use Gems\User\Mask\MaskRepository;
use Gems\User\Organization;
use Gems\User\User;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Respondent
{
    // use GenderTranslation;

    /**
     *
     * @var array The gems respondent and respondent to org data
     */
    protected $_gemsData;

    protected int $currentUserId;

    /**
     *
     * @var boolean true if Respondent exists in the database
     */
    public bool $exists = false;

    /**
     *
     * @var int The highest grs_phone_nr phone number used in this project
     */
    protected int $maxPhoneNumber = 4;

    /**
     *
     * @var string Respondent language
     */
    protected string|null $respondentLanguage = null;

    /**
     *
     * @param string $patientId   Patient number, you can use $respondentId instead
     * @param int $organizationId Organization id
     * @param int $respondentId   Optional respondent id, used when patient id is empty
     */
    public function __construct(
        protected ?string                          $patientId,
        protected int                              $organizationId,
        protected int|null                         $respondentId = null,
        protected readonly ConsentRepository       $consentRepository,
        protected readonly MailRepository          $mailRepository,
        protected readonly MaskRepository          $maskRepository,
        protected readonly OrganizationRepository  $organizationRepository,
        protected readonly ReceptionCodeRepository $receptionCodeRepository,
        protected readonly RespondentModel         $respondentModel,
        protected readonly ResultFetcher           $resultFetcher,
        protected readonly TranslatorInterface     $translator,
        protected readonly Translated              $translatedUtil,
        protected readonly Tracker                 $tracker,
        protected readonly TrackEvents             $trackEvents,
        CurrentUserRepository                      $currentUserRepository,
    )
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
        $this->respondentModel->applyStringAction('edit', true);

        $this->refresh();
    }

    public function assertAccessFromOrganizationId(User $currentUser, int $organizationId): void
    {
        $currentUser->assertAccessToOrganizationId($organizationId);
    }

    /**
     * Can respondent be mailed
     *
     * @return boolean
     */
    public function canBeMailed(): bool
    {
        return $this->_gemsData['gr2o_mailable'] && $this->_gemsData['gr2o_email'];
    }

    /**
     * Returns current age or at a given date when supplied
     *
     * @param \DateTimeInterface|null $date
     * @return int
     */
    public function getAge(DateTimeInterface|null $date = null, bool $months = false): ?int
    {
        $birthDate = $this->getBirthDay();
        if (! $birthDate instanceof DateTimeInterface) {
            return null;
        }

        if (is_null($date)) {
            $date = new DateTimeImmutable();
        } elseif (! $date instanceof DateTimeInterface) {
            return null;
        }

        // Now calculate age
        $diff = $birthDate->diff($date);

        if ($months) {
            return ($diff->y * 12) + $diff->m;
        }

        return $diff->y;
    }

    /**
     * Creates a copy of the data data
     *
     * @return array
     */
    public function getArrayCopy(): array
    {
        return $this->_gemsData;
    }

    /**
     * Get the birthdate
     *
     * @return \DateTimeInterface|null
     */
    public function getBirthday(): DateTimeInterface|null
    {
        if ($this->_gemsData['grs_birthday'] instanceof DateTimeInterface) {
            return $this->_gemsData['grs_birthday'];
        }
        return null;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity(): string|null
    {
        return $this->_gemsData['grs_city'];
    }

    /**
     * Get the birthdate
     *
     * @return \Gems\Util\ConsentCode
     */
    public function getConsent()
    {
        return $this->consentRepository->getConsentFromDescription($this->_gemsData['gr2o_consent']);
    }

    /**
     *
     * @param string $fieldName
     * @return ?DateTimeInterface
     */
    public function getDate($fieldName) : ?DateTimeInterface
    {
        if (isset($this->_gemsData[$fieldName])) {
            $date = $this->_gemsData[$fieldName];

            if ($date) {
                if ($date instanceof DateTimeInterface) {
                    return $date;
                }

                return \MUtil\Model::getDateTimeInterface($date, [Tracker::DB_DATETIME_FORMAT, Tracker::DB_DATE_FORMAT]);
            }
        }
        return null;
    }

    /**
     * Get the proper Dear mr./mrs/ greeting of respondent
     *
     * @return string
     */
    public function getDearGreeting(string $language = null): string
    {
        if ($language === null) {
            $language = $this->getLanguage();
        }

        $genderDears = $this->translatedUtil->getGenderDear($language);

        $gender = $this->getGender();
        if (isset($genderDears[$gender])) {
            $greeting = $genderDears[$gender] . ' ';
        } else {
            $greeting = '';
        }

        return $greeting . $this->getLastName();
    }

    /**
     * Get Email address of respondent
     *
     * @return string|null
     */
    public function getEmailAddress(): string|null
    {
        return $this->_gemsData['gr2o_email'];
    }

    /**
     * Get First name of respondent
     * @return string
     */
    public function getFirstName(): string|null
    {
        return $this->_gemsData['grs_first_name'];
    }

    /**
     * Get the formal name of respondent
     * @return string
     */
    public function getFullName(): string
    {
        $genderGreetings = $this->translatedUtil->getGenderHello($this->getLanguage());

        $greeting = isset($genderGreetings[$this->getGender()]) ? $genderGreetings[$this->getGender()] : '';

        return $greeting . ' ' . $this->getName();
    }

    /**
     * Get a single char code for the gender (normally M/F/U), or null when masked.
     * @return string|null
     */
    public function getGender(): string|null
    {
        return $this->_gemsData['grs_gender'];
    }

    /**
     * Get the proper greeting of respondent
     * @return string
     */
    public function getGreeting(string $language = null): string
    {
        if ($language === null) {
            $language = $this->getLanguage();
        }

        $genderGreetings = $this->translatedUtil->getGenderGreeting($language);

        $gender = $this->getGender();
        if (isset($genderGreetings[$gender])) {
            $greeting = $genderGreetings[$gender] . ' ';
        } else {
            $greeting = '';
        }

        return $greeting . $this->getLastName();
    }

    /**
     * Get the propper greeting of respondent
     * @return string
     */
    public function getGreetingNL(): string
    {
        $genderGreetings = $this->translatedUtil->getGenderGreeting($this->getLanguage());

        $greeting = $genderGreetings[$this->_gemsData['grs_gender']];

        return $greeting . ' ' . ucfirst($this->getLastName());
    }

    /**
     *
     * @return int The respondent id
     */
    public function getId(): int|null
    {
        return $this->respondentId;
    }

    /**
     * Get the respondents preferred language
     * @return string
     */
    public function getLanguage(): string
    {
        if (!isset($this->respondentLanguage)) {
            $this->respondentLanguage = $this->_gemsData['grs_iso_lang'];
        }
        return $this->respondentLanguage;
    }

    /**
     * Get Last name of respondent
     * @return string
     */
    public function getLastName(): string
    {
        $lastname = '';
        if (!empty($this->_gemsData['grs_surname_prefix'])) {
            $lastname .= $this->_gemsData['grs_surname_prefix'] . ' ';
        }
        $lastname .= $this->_gemsData['grs_last_name'];
        return $lastname;
    }

    public function getMailCode(): int
    {
        if (!array_key_exists('gr2o_mailable', $this->_gemsData)) {
            $this->refresh();
        }

        return $this->_gemsData['gr2o_mailable'];
    }

    /**
     * Get the full name (firstname, prefix and last name)
     * @return string
     */
    public function getName(): string
    {
        $fullName = $this->getFirstName() . ' ' . $this->getLastName();

        return $fullName;
    }

    /**
     *
     * @return \Gems\User\Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organizationRepository->getOrganization($this->organizationId);
    }

    /**
     *
     * @return integer Organization ID
     */
    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber(): string
    {
        return $this->patientId;
    }

    /**
     * Get the first entered phonenumber of the respondent.
     *
     * @return string
     */
    public function getPhonenumber(): string|null
    {
        for ($i = 1; $i <= $this->maxPhoneNumber; $i++) {
            if (isset($this->_gemsData['grs_phone_' . $i]) && ! empty($this->_gemsData['grs_phone_' . $i])) {
                return $this->_gemsData['grs_phone_' . $i];
            }
        }

        return null;
    }

    /**
     * Get the Mobile phone number specifically. In some projects this is fixed to a specific field
     *
     * @return string|null
     */
    public function getMobilePhoneNumber(): string|null
    {
        return $this->getPhonenumber();
    }

    /**
     * Return the ReceptionCode object
     *
     * @return ReceptionCode reception code
     */
    public function getReceptionCode(): ReceptionCode
    {
        return $this->receptionCodeRepository->getReceptionCode($this->_gemsData['gr2o_reception_code']);
    }

    /**
     *
     * @return \Gems\Model\Respondent\RespondentModel
     */
    public function getRespondentModel(): RespondentModel
    {
        return $this->respondentModel;
    }

    /**
     * Get the propper salutation of respondent
     * @return string
     */
    public function getSalutation(string $language = null): string
    {
        if ($language === null) {
            $language = $this->getLanguage();
        }
        return sprintf($this->translator->_('Dear %s', [], $this->getGender(), $language), $this->getGreeting());
    }

    /**
     * Get street address
     *
     * @return string
     */
    public function getStreetAddress(): string
    {
        return $this->_gemsData['grs_address_1'];
    }

    /**
     * Get zip code
     *
     * @return string
     */
    public function getZip(): string
    {
        return $this->_gemsData['grs_zipcode'];
    }

    /**
     * @return boolean True when something changed
     */
    public function handleChanged()
    {
        $changeEventClass = $this->getOrganization()->getRespondentChangeEventClass();
        if ($changeEventClass) {
            $event = $this->trackEvents->loadRespondentChangedEvent($changeEventClass);

            if ($event->processChangedRespondent($this)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has the respondent active tracks
     *
     * @return boolean
     */
    public function hasActiveTracks(): bool
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2track')
            ->columns(['gr2t_id_respondent_track'])
            ->join('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', [])
            ->where([
                'grc_success' => 1,
                'gr2t_id_user' => $this->respondentId,
                'gr2t_id_organization' => $this->organizationId,
            ])
            ->limit(1);

        return (boolean) $this->resultFetcher->fetchOne($select);
    }

    /**
     * Has the respondent active tracks
     *
     * @return bool
     */
    public function hasAnyTracks(): bool
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2track')
            ->columns(['gr2t_id_respondent_track'])
            ->where([
                'gr2t_id_user' => $this->respondentId,
                'gr2t_id_organization' => $this->organizationId,
            ])
            ->limit(1);

        return (boolean) $this->resultFetcher->fetchOne($select);
    }

    /**
     * Can mails be sent for this respondent?
     *
     * This only check the mailable attribute, not the presence of a mailaddress
     *
     * @return bool
     */
    public function isMailable(): bool
    {
        if (!array_key_exists('gr2o_mailable', $this->_gemsData)) {
            $this->refresh();
        }

        $noMailCode = $this->mailRepository->getRespondentNoMailCodeValue();

        return $this->_gemsData['gr2o_mailable'] > $noMailCode;
    }

    /**
     * Refresh the data
     */
	public function refresh(): void
    {
        $filter  = [];

        if ($this->patientId) {
            $filter['gr2o_patient_nr'] = $this->patientId;
        } elseif ($this->respondentId) {
            $filter['gr2o_id_user'] = $this->respondentId;
        }
        if (! $filter) {
            // Otherwise we load the first patient in the current organization
            $filter[] = '1=0';
        }
        if ($this->organizationId) {
            $filter['gr2o_id_organization'] = $this->organizationId;
        }

        $this->_gemsData = $this->respondentModel->loadFirst($filter);

        if ($this->_gemsData) {
            $this->exists = true;
            $this->respondentId   = $this->_gemsData['gr2o_id_user'];
            $this->patientId = $this->_gemsData['gr2o_patient_nr'];
        } else {
            $this->_gemsData = $this->respondentModel->loadNew();
            $this->exists = false;
        }

        $this->_gemsData = $this->maskRepository->applyMaskToRow($this->_gemsData);
	}

    /**
     * Restores tracks for a respondent, when the reception code matches the given $oldCode
     *
     * Used when restoring a respondent, and the restore tracks box is checked. This will
     * also restore all tokens in the tracks that have the same codes.
     *
     * @param ReceptionCode $oldCode The old reception code
     * @param ReceptionCode $newCode the new reception code
     * @return int  The number of restored tracks
     */
    public function restoreTracks(ReceptionCode $oldCode, ReceptionCode $newCode): int
    {
        $count      = 0;

        if (!$oldCode->isSuccess() && $newCode->isSuccess()) {
            $respTracks = $this->tracker->getRespondentTracks(
                    $this->getId(),
                    $this->getOrganizationId()
                    );

            foreach ($respTracks as $respTrack) {
                if ($respTrack instanceof RespondentTrack) {
                    if ($oldCode->getCode() === $respTrack->getReceptionCode()->getCode()) {
                        $respTrack->setReceptionCode($newCode, null, $this->currentUserId);
                        $respTrack->restoreTokens($oldCode, $newCode);
                        $count++;
                    } else {
                        // If the code was not assigned to the track, still try to restore tokens
                        $tmpCount = $respTrack->restoreTokens($oldCode, $newCode);
                        $count = $count + min($tmpCount, 1);
		    }
                }
            }
        }

        return $count;
    }

    /**
     * Overwrite the respondents preferred language
     */
    public function setLocale(string $locale): void
    {
        $this->respondentLanguage = $locale;
    }

    public function setMenu(MenuSnippetHelper $menuSnippetHelper, TranslatorInterface $translator): void
    {
        $menu = $menuSnippetHelper->getRouteMenu('respondent.delete');

        if ($menu) {
            if ($this->getReceptionCode()->isSuccess()) {
                $menu->setLabel($translator->_('Deactivate'));
            } else {
                $menu->setLabel($translator->_('Reactivate'));
            }
        }
    }

    /**
     * Set the reception code for a respondent and cascade non-success codes to the
     * tracks / surveys.
     *
     * @param ReceptionCode|string $newCode String or \Gems\Util\ReceptionCode
     * @return ReceptionCode The new code reception code object for further processing
     */
    public function setReceptionCode(ReceptionCode|string $newCode): ReceptionCode
    {
        if (!$newCode instanceof ReceptionCode) {
            $newCode = $this->receptionCodeRepository->getReceptionCode($newCode);
        }

        // Perform actual save, but not for simple stop codes.
        if ($newCode->isForRespondents()) {
            $oldCode = $this->_gemsData['gr2o_reception_code'];

            // If the code wasn't set already
            if ($oldCode !== $newCode->getCode()) {
                $values['gr2o_reception_code'] = $newCode->getCode();
                $values['gr2o_changed']        = new Expression("CURRENT_TIMESTAMP");
                $values['gr2o_changed_by']     = $this->currentUserId;

                // Update though primamry key is prefered
                $where['gr2o_patient_nr'] = $this->getPatientNumber();
                $where['gr2o_id_organization'] = $this->getOrganizationId();

                $this->resultFetcher->updateTable('gems__respondent2org', $values, $where);
            }
        }

        // Is the respondent really removed
        if (! $newCode->isSuccess()) {
            // Only check for $respondentId when it is really needed

            // Cascade to tracks
            // the responsiblilty to handle it correctly is on the sub objects now.
            $tracks = $this->tracker->getRespondentTracks($this->getId(), $this->getOrganizationId());
            foreach ($tracks as $track) {
                $track->setReceptionCode($newCode, null, $this->currentUserId);
            }
        }

        if ($newCode->isForRespondents()) {
            $this->handleChanged();
        }

        return $newCode;
    }
}
