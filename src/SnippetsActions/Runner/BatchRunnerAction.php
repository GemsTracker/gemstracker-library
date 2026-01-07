<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Runner
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Runner;

use Gems\Batch\BatchRunner;
use Gems\Snippets\Batch\BatchRunnerSnippet;
use Zalt\SnippetsActions\AbstractAction;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Runner
 * @since      Class available since version 1.0
 */
class BatchRunnerAction extends AbstractAction
{
    protected array $_snippets = [BatchRunnerSnippet::class];

    public BatchRunner $batchRunner;
}