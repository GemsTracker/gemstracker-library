<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Show;

use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\Usage\EnableUsageSnippet;
use Gems\Snippets\Usage\UsageSnippet;
use Gems\SnippetsActions\UsageCounterActionTrait;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Show
 * @since      Class available since version 1.0
 */
class ShowWithUsageAction extends ShowAction
{
    use UsageCounterActionTrait;

    /**
     * @inheritDoc
     */
    protected array $_snippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CurrentButtonRowSnippet::class,
        EnableUsageSnippet::class,
    ];

    public array $usageSnippets = [
        UsageSnippet::class,
    ];
}