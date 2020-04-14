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
 * @since      Class available since version 1.8.8 01-Apr-2020 16:01:10
 */
interface DeferredUserLoaderInterface extends HelperInterface
{
    /**
     * Get the deferred user
     *
     * @param \Gems_User_User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return \Gems_User_user|null
     */
    public function getDeferredUser(\Gems_User_User $embeddedUser, $deferredLogin);
}