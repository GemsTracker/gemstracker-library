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
use Gems\User\User;
use Laminas\Authentication\Result;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\Auth
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 17:30:06
 */
class PasswordVerify extends EmbeddedAuthAbstract
{
    /**
     * Authenticate embedded user
     *
     * @param User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(User $user, string $secretKey): bool
    {
        $embeddedUserData = $user->getEmbedderData();
        if ($embeddedUserData) {
            $deferredUser = $embeddedUserData->getDeferredUser($user, $this->deferredLogin);
        } else {
            $deferredUser = null;
        }

        if ($deferredUser) {
            $result = $deferredUser->authenticate($secretKey);

            if ($result instanceof Result) {
                return $result->isValid();
            }

            return (boolean) $result;
        }

        return false;
    }

    /**
     *
     * @param User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(User $user): string
    {
        return '{user_password}';
    }

    /**
     *
     * @return string Something to display as label.
     */
    public function getLabel(): string
    {
        return $this->translator->_('NOT SAFE: Final user PHP Password verify');
    }
}
