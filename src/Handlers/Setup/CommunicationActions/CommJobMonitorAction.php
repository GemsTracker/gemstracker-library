<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Monitor
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup\CommunicationActions;

use Gems\Snippets\MonitorSnippet;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Monitor
 * @since      Class available since version 1.0
 */
class CommJobMonitorAction extends \Zalt\SnippetsActions\AbstractAction
{
    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [MonitorSnippet::class];

    /**
     * @var string The monito job to use
     */
    protected string $currentMonitor = MonitorSnippet::CRON_MAIl;
}