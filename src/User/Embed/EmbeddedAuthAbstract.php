<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed;

use Gems\User\User;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 16:04:38
 */
abstract class EmbeddedAuthAbstract implements EmbeddedAuthInterface
{
    /**
     *
     * @var string User to defer to after authentication
     */
    protected string $deferredLogin;

    /**
     *
     * @var array Organization or organizations for the user to try to login with
     */
    protected array $organizations;

    /**
     *
     * @var string Patient id to show afterwards
     */
    protected string $patientNumber;

    /**
     *
     * @var string Reason for last authentication failure
     */
    protected string $errorMessage = '';

    public function __construct(
        protected TranslatorInterface $translator
    )
    {}

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // abstract public function getLabel();

    /**
     * Authenticate embedded user
     *
     * @param User $user
     * @param $secretKey
     * @return bool
     */
    // abstract public function authenticate(\Gems\User\User $user, $secretKey);

    /**
     *
     * @param string $value User to defer to after authentication
     */
    public function setDeferredLogin(string $value): void
    {
        $this->deferredLogin = $value;
    }

    /**
     *
     * @param array $value Organization or organizations for the user to try to login with
     */
    public function setOrganizations(array $value): void
    {
        $this->organizations = $value;
    }

    /**
     *
     * @param string $value Patient id to show afterwards
     */
    public function setPatientNumber(string $value): void
    {
        $this->patientNumber = $value;
    }

    /**
     *
     * @return string Reason for last authentication failure
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     *
     * @param string $message Reason for last authentication failure
     */
    public function setErrorMessage(string $message): void
    {
        $this->errorMessage = $message;
    }

    /**
     * Set failure message and fail authentication.
     * 
     * @param string $message Reason for authentication failure
     * @return false Always false
     */
    public function failAuthentication(string $message): bool
    {
        $this->setErrorMessage($message);
        return false;
    }

}
