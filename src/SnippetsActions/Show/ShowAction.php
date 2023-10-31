<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Show
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Show;

use Gems\Snippets\Generic\ContentTitleSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\ModelDetailTableSnippet;
use Gems\Snippets\UsageSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Gems\SnippetsActions\ContentTitleActionTrait;
use Gems\SnippetsActions\UsageCounterActionTrait;
use Zalt\Model\MetaModellerInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Show
 * @since      Class available since version 1.9.2
 */
class ShowAction extends \Zalt\SnippetsActions\Show\ShowAction
{
    use ButtonRowActiontrait;
    use ContentTitleActionTrait;
    use UsageCounterActionTrait;

    /**
     * @inheritDoc
     */
    protected array $_snippets = [
        ContentTitleSnippet::class,
        ModelDetailTableSnippet::class,
        CurrentButtonRowSnippet::class,
        UsageSnippet::class,
        ];

    public string $class = 'displayer table';

    public MetaModellerInterface $model;
}