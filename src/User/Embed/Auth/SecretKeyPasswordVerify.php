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
class SecretKeyPasswordVerify extends EmbeddedAuthAbstract
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

        return password_verify($secretKey, $savedKey);
    }

    /**
     *
     * @param User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(User $user, EmbeddedUserData $embeddedUserData): string
    {
        return '{system_user_key}';
    }

    /**
     *
     * @return string Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel(): string
    {
        return $this->translator->_('UNSAFE: System user secret key password verify');
    }
}