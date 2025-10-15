<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Import;

use Gems\Snippets\ModelImportSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Import
 * @since      Class available since version 1.0
 */
class ImportAction extends \Gems\SnippetsActions\Browse\BrowseFilteredAction
{
    use ButtonRowActiontrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ModelImportSnippet::class,
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

    public static function getPagePrivilege(): string
    {
        return 'import';
    }
}