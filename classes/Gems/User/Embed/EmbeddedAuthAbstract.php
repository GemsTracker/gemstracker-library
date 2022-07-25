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

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 16:04:38
 */
abstract class EmbeddedAuthAbstract extends \MUtil\Translate\TranslateableAbstract implements EmbeddedAuthInterface
{
    /**
     *
     * @var string User to defer to after authentication
     */
    protected $deferredLogin;

    /**
     *
     * @var mixed Organization or organizations for the user to try to login with
     */
    protected $organizations;

    /**
     *
     * @var string Patient id to show afterwards
     */
    protected $patientNumber;

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // abstract public function getLabel();

    /**
     * Authenticate embedded user
     *
     * @param \Gems\User\User $user
     * @param $secretKey
     * @return bool
     */
    // abstract public function authenticate(\Gems\User\User $user, $secretKey);

    /**
     *
     * @param string $value User to defer to after authentication
     */
    public function setDeferredLogin($value)
    {
        $this->deferredLogin = $value;
    }

    /**
     *
     * @param mixed $value Organization or organizations for the user to try to login with
     */
    public function setOrganizations($value)
    {
        $this->organizations = $value;
    }

    /**
     *
     * @param string $value Patient id to show afterwards
     */
    public function setPatientNumber($value)
    {
        $this->patientNumber = $value;
    }
}