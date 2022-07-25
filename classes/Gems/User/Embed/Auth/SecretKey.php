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
     * @param \Gems\User\User $user
     * @param $secretKey
     * @return bool
     */
    public function authenticate(\Gems\User\User $user, $secretKey)
    {
        $savedKey = $user->getSecretKey();

        if ($savedKey == $secretKey) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param \Gems\User\User $user
     * @return string An optionally working login key
     */
    public function getExampleKey(\Gems\User\User $user)
    {
        return $user->getSecretKey();
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel()
    {
        return $this->_('UNSAFE: Compare to secret key');
    }
}