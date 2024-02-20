<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed\Auth;

use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\User;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 17:32:24
 */
class SecretKey extends EmbeddedAuthAbstract
{
    /**
     * Authenticate embedded user
     *
     * @param User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(User $user, EmbeddedUserData $embeddedUserData, string $secretKey): bool
    {
        $savedKey = $embeddedUserData->getSecretKey();

        if ($savedKey == $secretKey) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(User $user, EmbeddedUserData $embeddedUserData): string
    {
        return $embeddedUserData->getSecretKey();
    }

    /**
     *
     * @return string Something to display as label.
     */
    public function getLabel(): string
    {
        return $this->translator->_('UNSAFE: Compare to secret key');
    }
}