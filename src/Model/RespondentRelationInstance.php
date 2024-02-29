<?php

/**
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Registry\TargetAbstract;
use Gems\Tracker\Token;
use Gems\Util\Translated;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class RespondentRelationInstance
{
    protected DateTimeInterface|null $birthdate = null;
    protected string|null $email = null;
    protected string|null $firstName = null;
    protected string $gender = 'U';
    protected int|null $id = null;
    protected string|null $lastName = null;
    protected int $mailable = 100;
    protected string|null $phone = null;



    public function __construct(
        array $data,
        protected readonly Translated $translatedUtil,
    )
    {
        // Todo Hydrate
        $this->id = $data['grr_id'] ?? null;
        $this->firstName = $data['grr_first_name'] ?? null;
        $this->lastName = $data['grr_last_name'] ?? null;
        $this->birthdate = $data['grr_birthdate'] ?? null;
        $this->email = $data['grr_email'] ?? null;
        $this->gender = $data['grr_gender'] ?? 'U';
        $this->phone = $data['grr_phone'] ?? null;
        if (array_key_exists('grr_mailable', $data)) {
            $this->mailable = $data['grr_mailable'];
        }
    }

    public function __toString(): string
    {
        return serialize([
            'grr_first_name' => $this->firstName,
            'grr_last_name'  => $this->lastName,
            'grr_email'      => $this->email,
            'grr_phone'      => $this->phone,
            'grr_mailable'   => (int)$this->mailable,
        ]);
    }

    /**
     * Returns current age or at a given date when supplied
     *
     * @param ?DateTimeInterface $date To comare with
     * @return int
     */
    public function getAge(DateTimeInterface|null $date = null): ?DateTimeImmutable
    {
        $birthDate = $this->getBirthDate();
        if (! $birthDate instanceof DateTimeInterface) {
            return null;
        }

        if ($date === null) {
            $date = new DateTimeImmutable();
        } elseif (! $date instanceof DateTimeInterface) {
            return null;
        }
        
        // Now calculate age
        $diff = $birthDate->diff($date);
        
        return $diff->y;
    }

    public function getBirthDate(): DateTimeInterface|null
    {
        return $this->getBirthDate();
    }

    /**
     * Get the proper Dear mr./mrs/ greeting of respondent
     * 
     * @return string
     */
    public function getDearGreeting(string $language): string
    {
        $genderDears = $this->translatedUtil->getGenderDear($language);

        $gender = $this->getGender();
        if (isset($genderDears[$gender])) {
            $greeting = $genderDears[$gender] . ' ';
        } else {
            $greeting = '';
        }

        return $greeting . $this->getLastName();
    }

    public function getEmail(): string|null
    {
        return $this->getEmail();
    }

    public function getFirstName(): string|null
    {
        return $this->getFirstName();
    }

    /**
     * M / F / U
     *
     * @return string
     */
    public function getGender(): string
    {
        return $this->gender;
    }

    public function getGreeting(string $language): string
    {
        $genderGreetings = $this->translatedUtil->getGenderGreeting($language);
        $greeting = $genderGreetings[$this->getGender()] . ' ' . ucfirst($this->getLastName());

        return $greeting;
    }

    public function getHello(string $language): string
    {
        $genderHello = $this->translatedUtil->getGenderHello($language);
        $hello = $genderHello[$this->getGender()] . ' ' . ucfirst($this->getLastName());

        return $hello;
    }

    public function getLastName(): string
    {
        return $this->getLastName();
    }

    public function getMailCode(): int
    {
        return $this->getMailCode();
    }

    public function getRelationId(): int|null
    {
        return $this->id;
    }

    /**
     * Return string with first and lastname, separated with a space
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function getPhoneNumber(): string|null
    {
        return $this->phone;
    }

    /**
     * Can mails be sent for this relation?
     *
     * This only check the mailable attribute, not the presence of a mailaddress
     *
     * @return boolean
     */
    public function isMailable(): bool
    {
        return (bool)$this->mailable;
    }



}
