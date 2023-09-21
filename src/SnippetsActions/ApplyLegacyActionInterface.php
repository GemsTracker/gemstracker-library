<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions;

use phpDocumentor\Reflection\Types\Boolean;

/**
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.0
 */
interface ApplyLegacyActionInterface extends \Zalt\SnippetsActions\ApplyActionInterface
{
    /**
     * @param string $action Apply this action to this object
     * @param boolean $detailed
     * @return void
     */
    public function applyStringAction(string $action, bool $detailed): void;
}