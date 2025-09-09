<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Export;

use Gems\SnippetsActions\Export\ExportAction;
use Gems\SnippetsActions\PagePrivilegeInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Export
 * @since      Class available since version 1.0
 */
class GenerateAction extends ExportAction implements PagePrivilegeInterface
{
    public static function getPagePrivilege(): string
    {
        return 'index';
    }
}