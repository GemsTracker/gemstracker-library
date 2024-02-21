<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Default
 * @license    New BSD License
 */

namespace Gems\SnippetsActions\Download;

use Gems\Snippets\File\DownloadFileSnippet;
use Gems\SnippetsActions\Show\ShowAction;

class DownloadFileAction extends ShowAction
{
    /**
     * @inheritDoc
     */
    protected array $_snippets = [
        DownloadFileSnippet::class,
    ];

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
}
