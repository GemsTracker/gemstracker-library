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

use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\AuthNew\Adapter\GemsTrackerAuthentication;
use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\User;
use Laminas\Authentication\Result;
use Laminas\Db\Adapter\Adapter;
use Zalt\Base\TranslatorInterface;

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
    public function __construct(
        TranslatorInterface $translator,
        protected readonly Adapter $db,
    )
    {
        parent::__construct($translator);
    }

    /**
     * Authenticate embedded user
     *
     * @param User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(User $user, EmbeddedUserData $embeddedUserData, string $secretKey): bool
    {
        $deferredUser = $embeddedUserData->getDeferredUser($user, $this->deferredLogin);

        if ($deferredUser) {
            $authentication = GemsTrackerAuthentication::fromUser($this->db, $deferredUser, $secretKey);
            $result = $authentication->authenticate();

            if ($result instanceof AuthenticationResult) {
                return $result->isValid();
            }

            return (bool) $result;
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
