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

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 15:59:39
 */
interface EmbeddedAuthInterface extends HelperInterface
{
    /**
     * Authenticate embedded user
     *
     * @param User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(User $user, EmbeddedUserData $embeddedUserData, string $secretKey): bool;

    /**
     *
     * @param User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(User $user, EmbeddedUserData $embeddedUserData): string;

    /**
     *
     * @param string $value User to defer to after authentication
     */
    public function setDeferredLogin(string $value): void;

    /**
     *
     * @param array $value Organization or organizations for the user to try to login with
     */
    public function setOrganizations(array $value): void;

    /**
     *
     * @param string $value Patient id to show afterwards
     */
    public function setPatientNumber(string $value): void;
}
