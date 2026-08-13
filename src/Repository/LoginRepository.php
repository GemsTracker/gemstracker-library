<?php

declare(strict_types=1);

/**
 * Repository to allow customization of login screen with texts
 *
 * @package    Gems
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Repository;

/**
 * @package    Gems
 * @subpackage Repository
 * @since      Class available since version 1.0
 */
class LoginRepository
{
    public function getLoginTexts(): array
    {
        return [];
    }
}