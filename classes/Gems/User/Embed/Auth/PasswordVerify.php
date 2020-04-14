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
     * @param \Gems_User_User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(\Gems_User_User $user, $secretKey)
    {
        $user = $user->getDeferredUser($this->deferredLogin);

        if ($user) {
            $result = $user->authenticate($secretKey);

            if ($result instanceof Result) {
                return $result->isValid();
            }

            return (boolean) $result;
        }

        return false;
    }

    /**
     *
     * @param \Gems_User_User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(\Gems_User_User $user)
    {
        return '{user_password}';
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getLabel()
    {
        return $this->_('NOT SAFE: Final user PHP Password verify');
    }
}