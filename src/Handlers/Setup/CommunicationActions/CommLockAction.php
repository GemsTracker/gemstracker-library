<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Lock
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup\CommunicationActions;

use Gems\Snippets\Communication\CommLockSwitchSnippet;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Lock
 * @since      Class available since version 1.0
 */
class CommLockAction extends \Zalt\SnippetsActions\AbstractAction
{
    protected ?string $_action = 'lock';

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [CommLockSwitchSnippet::class];
}