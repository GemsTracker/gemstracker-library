<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Handlers\Setup\CommunicationActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup\CommunicationActions;

use Gems\Snippets\Communication\CommJobButtonRowSnippet;
use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\Token\PlanTokenSnippet;
use Gems\Snippets\TokenPlanTableSnippet;
use Gems\Snippets\Tracker\TokenStatusLegenda;
use Gems\Snippets\Usage\EnableUsageSnippet;

/**
 * @package    Gems
 * @subpackage Handlers\Setup\CommunicationActions
 * @since      Class available since version 1.0
 */
class CommJobShowAction extends \Gems\SnippetsActions\Show\ShowWithUsageAction
{
    /**
     * @inheritDoc
     */
    protected array $_snippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CommJobButtonRowSnippet::class,
        EnableUsageSnippet::class,
    ];

    public array $tokenParams = [];

    public array $usageSnippets = [
        PlanTokenSnippet::class,
        TokenStatusLegenda::class,
    ];
}