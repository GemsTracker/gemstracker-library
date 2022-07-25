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
 * @since      Class available since version 1.8.8 01-Apr-2020 15:59:39
 */
interface EmbeddedAuthInterface extends HelperInterface
{
    /**
     * Authenticate embedded user
     *
     * @param \Gems\User\User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(\Gems\User\User $user, $secretKey);

    /**
     *
     * @param \Gems\User\User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(\Gems\User\User $user);
}