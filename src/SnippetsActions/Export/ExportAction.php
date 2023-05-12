<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Export;

use Gems\Snippets\Export\ExportBatchSnippet;
use Gems\Snippets\Export\ExportDownloadSnippet;
use Gems\Snippets\Export\ExportFormSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Gems\Task\TaskRunnerBatch;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ModelActionInterface;
use Zalt\SnippetsActions\ModelActionTrait;
use Zalt\SnippetsActions\PostActionInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @since      Class available since version 1.0
 */
class ExportAction extends AbstractAction implements ModelActionInterface, PostActionInterface
{
    use ButtonRowActiontrait;
    use ModelActionTrait;

    const STEP_BATCH = 'batch';
    const STEP_DOWNLOAD = 'download';
    const STEP_FORM = 'form';
    const STEP_RESET = 'reset';

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ExportFormSnippet::class,
        ExportBatchSnippet::class,
        ExportDownloadSnippet::class,
        ];

    public TaskRunnerBatch $batch;

    public string $formTitle = '';

    public string $step = self::STEP_FORM;
}