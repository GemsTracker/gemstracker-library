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
use Zalt\SnippetsActions\ParameterActionInterface;
use Zalt\SnippetsActions\PostActionInterface;

class DownloadFileAction extends ShowAction implements PostActionInterface, ParameterActionInterface
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

    public string|null $directory = null;
}
