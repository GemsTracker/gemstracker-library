<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @license    New BSD License
 */

namespace Gems\User\Embed;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @license    New BSD License
 * @since      Class available since version v2.0.54
 */
interface UpdatingAuthInterface extends EmbeddedAuthInterface
{
    /**
     *
     * @return string User to defer to after authentication
     */
    public function getDeferredLogin(): string;

    /**
     *
     * @return array Organization or organizations for the user to try to login with
     */
    public function getOrganizations(): array;

    /**
     *
     * @return string Patient id to show afterwards
     */
    public function getPatientNumber(): string;
}
