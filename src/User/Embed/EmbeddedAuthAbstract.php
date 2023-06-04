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
use MUtil\Translate\Translator;

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
     * @var mixed Organization or organizations for the user to try to login with
     */
    protected array $organizations;

    /**
     *
     * @var string Patient id to show afterwards
     */
    protected string $patientNumber;

    public function __construct(protected Translator $translator)
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
     * @param mixed $value Organization or organizations for the user to try to login with
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
}