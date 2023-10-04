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
use Gems\SnippetsActions\Browse\BrowseFilteredAction;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Gems\Task\ExportRunnerBatch;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @since      Class available since version 1.0
 */
class ExportAction extends BrowseFilteredAction
{
    use ButtonRowActiontrait;

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

    /**
     * @var ExportRunnerBatch Set in ExportFormSnippet->hasHtmlOutput()
     */
    public ExportRunnerBatch $batch;

    /**
     * Field name for crsf protection field.
     *
     * @var string
     */
    public string $csrfName = '__csrf';

    /**
     * The csrf token.
     *
     * @var string
     */
    public ?string $csrfToken = null;

    public string $formTitle = '';

    public string $step = self::STEP_FORM;
}