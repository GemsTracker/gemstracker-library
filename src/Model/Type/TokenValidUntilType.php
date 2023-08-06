<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Type;

/**
 * @package    Gems
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class TokenValidUntilType extends TokenValidFromType
{
    /**
     * @var string The time that should not be displayed
     */
    protected string $maybeTimeValue = '23:59:59';
}
