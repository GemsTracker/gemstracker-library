<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage SnippetsActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions;

use Gems\Usage\UsageCounterInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions
 * @since      Class available since version 1.0
 */
trait UsageCounterActionTrait
{
    public UsageCounterInterface $usageCounter;
}