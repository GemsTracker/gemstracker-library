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

use Gems\Registry\TargetAbstract;
use Gems\Translate\GenderTranslation;
use Gems\Util\Translated;

use MUtil\Model;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Respondent extends TargetAbstract
{
    use GenderTranslation;

    /**
     *
     * @var array The gems respondent and respondent to org data
     */
    protected $_gemsData;

    /**
     * Allow login info to be loaded
     *
     * @var boolean
     */
    protected $addLoginCheck = false;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Boolean true if Respondent exists in the database
     */
    public $exists = false;

	/**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var int The highest grs_phone_nr phone number used in this project
     */
    protected $maxPhoneNumber = 4;

    /**
     *
     * @var \Gems\Model\RespondentModel
     */
	protected $model;

    /**
     *
     * @var integer Organization Id
     */
    protected $organizationId;

    /**
     *
     * @var string Patient Id
     */
    protected $patientId;

    /**
     * @var int respondentId
     */
    protected $respondentId;

    /**
     *
     * @var string Respondent language
     */
    protected $respondentLanguage;

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @param string $patientId   Patient number, you can use $respondentId instead
     * @param int $organizationId Organization id
     * @param int $respondentId   Optional respondent id, used when patient id is empty
     */
    public function __construct($patientId, $organizationId, $respondentId = null)
    {
        $this->patientId      = $patientId;
        $this->organizationId = $organizationId;
        $this->respondentId   = $respondentId;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->model = $this->loader->getModels()->getRespondentModel(true);
        if ($this->addLoginCheck) {
            $this->model->addLoginCheck();
        }
        $this->initGenderTranslations();
        // Load the data
        $this->refresh();
    }

    /**
     * Set menu parameters from this token
     *
     * @param \Gems\Menu\ParameterSource $source
     * @return \Gems\Tracker\Respondent (continuation pattern)
     */
    public function applyToMenuSource(\Gems\Menu\ParameterSource $source)
    {
        $source->setPatient($this->getPatientNumber(), $this->getOrganizationId());
        $source->offsetSet('resp_deleted', ($this->getReceptionCode()->isSuccess() ? 0 : 1));

        return $this;
    }

    /**
     * Can respondent be mailed
     *
     * @return boolean
     */
    public function canBeMailed()
    {
        return $this->_gemsData['gr2o_mailable'] && $this->_gemsData['gr2o_email'];
    }

    /**
     * Returns current age or at a given date when supplied
     *
     * @param \DateTimeInterface|null $date
     * @return int
     */
    public function getAge($date = null, $months = false): ?int
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
    public function getArrayCopy()
    {
        return $this->_gemsData;
    }

    /**
     * Get the birthdate
     *
     * @return \DateTimeInterface|null
     */
    public function getBirthday()
    {
        return $this->_gemsData['grs_birthday'];
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
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
        return $this->util->getConsent($this->_gemsData['gr2o_consent']);
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

                return Model::getDateTimeInterface($date, [\Gems\Tracker::DB_DATETIME_FORMAT, \Gems\Tracker::DB_DATE_FORMAT]);
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
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->_gemsData['gr2o_email'];
    }

    /**
     * Get First name of respondent
     * @return string
     */
    public function getFirstName()
    {
        return $this->_gemsData['grs_first_name'];
    }

    /**
     * Get the formal name of respondent
     * @return string
     */
    public function getFullName()
    {

        $genderGreetings = $this->translatedUtil->getGenderHello($this->getLanguage());

        $greeting = isset($genderGreetings[$this->getGender()]) ? $genderGreetings[$this->getGender()] : '';

        return $greeting . ' ' . $this->getName();
    }

    /**
     * Get a single char code for the gender (normally M/F/U)
     * @return string
     */
    public function getGender()
    {
        return $this->_gemsData['grs_gender'];
    }

    /**
     * Get the proper greeting of respondent
     * @return string
     */
    public function getGreeting(string $language = null)
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
    public function getGreetingNL()
    {
        $genderGreetings = $this->translatedUtil->getGenderGreeting($this->getLanguage());

        $greeting = $genderGreetings[$this->_gemsData['grs_gender']];

        return $greeting . ' ' . ucfirst($this->getLastName());
    }

    /**
     *
     * @return int The respondent id
     */
    public function getId()
    {
        return $this->respondentId;
    }

    /**
     * Get the respondents preferred language
     * @return string
     */
    public function getLanguage() 
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
    public function getLastName()
    {
        $lastname = '';
        if (!empty($this->_gemsData['grs_surname_prefix'])) {
            $lastname .= $this->_gemsData['grs_surname_prefix'] . ' ';
        }
        $lastname .= $this->_gemsData['grs_last_name'];
        return $lastname;
    }

    /**
     * Get the full name (firstname, prefix and last name)
     * @return string
     */
    public function getName()
    {
        $fullName = $this->getFirstName() . ' ' . $this->getLastName();

        return $fullName;
    }

    /**
     *
     * @return \Gems\User\Organization
     */
    public function getOrganization()
    {
        return $this->loader->getOrganization($this->organizationId);
    }

    /**
     *
     * @return integer Organization ID
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    /**
     * Get Patient number of respondent
     *
     * @deprecated since version 1.6.4
     * @return string
     */
    public function getPatientId()
    {
        return $this->patientId;
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        return $this->patientId;
    }

    /**
     * Get the first entered phonenumber of the respondent.
     *
     * @return string
     */
    public function getPhonenumber()
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
    public function getMobilePhoneNumber()
    {
        return $this->getPhonenumber();
    }

    /**
     * Return the \Gems\Util\ReceptionCode object
     *
     * @return \Gems\Util\ReceptionCode reception code
     */
    public function getReceptionCode()
    {
        return $this->util->getReceptionCode($this->_gemsData['gr2o_reception_code']);
    }

    /**
     *
     * @return \Gems\Model\RespondentModel
     */
    public function getRespondentModel()
    {
        return $this->model;
    }

    /**
     * Get the propper salutation of respondent
     * @return string
     */
    public function getSalutation(string $language = null)
    {
        if ($language === null) {
            $language = $this->getLanguage();
        }
        return sprintf($this->_('Dear %s', $language, $this->getGender()), $this->getGreeting());
    }

    /**
     * Get street address
     *
     * @return string
     */
    public function getStreetAddress()
    {
        return $this->_gemsData['grs_address_1'];
    }

    /**
     * Get zip code
     *
     * @return string
     */
    public function getZip()
    {
        return $this->_gemsData['grs_zipcode'];
    }

    /**
     * Has the respondent active tracks
     *
     * @return boolean
     */
    public function hasActiveTracks()
    {
        $select = $this->db->select()
                ->from('gems__respondent2track', ['gr2t_id_respondent_track'])
                ->joinInner('gems__reception_codes', 'gr2t_reception_code = grc_id_reception_code', [])
                ->where('grc_success = 1')
                ->where('gr2t_id_user = ?', $this->respondentId)
                ->where('gr2t_id_organization = ?', $this->organizationId)
                ->limit(1);

        return (boolean) $this->db->fetchOne($select);
    }

    /**
     * Has the respondent active tracks
     *
     * @return boolean
     */
    public function hasAnyTracks()
    {
        $select = $this->db->select()
                ->from('gems__respondent2track', ['gr2t_id_respondent_track'])
                ->where('gr2t_id_user = ?', $this->respondentId)
                ->where('gr2t_id_organization = ?', $this->organizationId)
                ->limit(1);

        return (boolean) $this->db->fetchOne($select);
    }

    /**
     * Can mails be sent for this respondent?
     *
     * This only check the mailable attribute, not the presence of a mailaddress
     *
     * @return boolean
     */
    public function isMailable()
    {
        if (!array_key_exists('gr2o_mailable', $this->_gemsData)) {
            $this->refresh();
        }

        $noMailCode = $this->util->getDbLookup()->getRespondentNoMailCodeValue();

        return $this->_gemsData['gr2o_mailable'] > $noMailCode;
    }

    /**
     * Refresh the data
     */
	public function refresh()
    {
        $default = true;
        $filter  = array();

        if ($this->patientId) {
            $filter['gr2o_patient_nr'] = $this->patientId;
            $default = false;
        } elseif ($this->respondentId) {
            $filter['gr2o_id_user'] = $this->respondentId;
            $default = false;
        }
        if (! $filter) {
            // Otherwise we load the first patient in the current organization
            $filter[] = '1=0';
        }
        if ($this->organizationId) {
            $filter['gr2o_id_organization'] = $this->organizationId;
        }

        $this->_gemsData = $this->model->loadFirst($filter);

        if ($this->_gemsData) {
            $this->exists = true;

            $this->patientId      = $this->_gemsData['gr2o_patient_nr'];
            $this->organizationId = $this->_gemsData['gr2o_id_organization'];
            $this->respondentId   = $this->_gemsData['gr2o_id_user'];
        } else {
            $this->_gemsData = $this->model->loadNew();
            $this->exists = false;
        }

        if ($this->currentUser instanceof \Gems\User\User) {
            $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
        }
	}

    /**
     * Restores tracks for a respondent, when the reception code matches the given $oldCode
     *
     * Used when restoring a respondent, and the restore tracks box is checked. This will
     * also restore all tokens in the tracks that have the same codes.
     *
     * @param \Gems\Util\ReceptionCode $oldCode The old reception code
     * @param \Gems\Util\ReceptionCode $newCode the new reception code
     * @return int  The number of restored tracks
     */
    public function restoreTracks(\Gems\Util\ReceptionCode $oldCode, \Gems\Util\ReceptionCode $newCode) {
        $count      = 0;

        if (!$oldCode->isSuccess() && $newCode->isSuccess()) {
            $respTracks = $this->loader->getTracker()->getRespondentTracks(
                    $this->getId(),
                    $this->getOrganizationId()
                    );

            foreach ($respTracks as $respTrack) {
                if ($respTrack instanceof \Gems\Tracker\RespondentTrack) {
                    if ($oldCode->getCode() === $respTrack->getReceptionCode()->getCode()) {
                        $respTrack->setReceptionCode($newCode, null, $this->currentUser->getUserId());
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
    public function setLocale($locale)
    {
        $this->respondentLanguage = $locale;
    }

    /**
     * Set the reception code for a respondent and cascade non-success codes to the
     * tracks / surveys.
     *
     * @param string $newCode     String or \Gems\Util\ReceptionCode
     * @return \Gems\Util\ReceptionCode The new code reception code object for further processing
     */
    public function setReceptionCode($newCode)
    {
        return $this->model->setReceptionCode(
                $this->getPatientNumber(),
                $this->getOrganizationId(),
                $newCode,
                $this->getId(),
                $this->getReceptionCode()
                );
    }
}
