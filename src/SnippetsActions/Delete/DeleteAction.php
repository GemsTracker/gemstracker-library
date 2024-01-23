<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Delete
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Delete;

use Gems\Snippets\ModelConfirmDeleteSnippet;
use Gems\Snippets\Usage\UsageSnippet;
use Gems\SnippetsActions\ContentTitleActionTrait;
use Gems\SnippetsActions\UsageCounterActionTrait;
use Zalt\Model\MetaModellerInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Delete
 * @since      Class available since version 1.9.2
 */
class DeleteAction extends \Zalt\SnippetsActions\Delete\DeleteAction
{
    use ContentTitleActionTrait;
    use UsageCounterActionTrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ModelConfirmDeleteSnippet::class,
        UsageSnippet::class,
    ];

    /**
     * @var string Optional class for use on No button
     */
    public ?string $buttonNoClass = 'actionlink btn';

    /**
     * @var ?string Optional class for use on Yes button
     */
    public ?string $buttonYesClass = 'actionlink btn btn-primary btn-lg';

    /**
     * @var string A classname to append to the class attribute ofUseSn the main HtmlElement output of the snippet;
     */
    public string $class = 'displayer';

    /**
     * @var string Optional title to display at the head of this page.
     */
    public string $displayTitle = '';

    public MetaModellerInterface $model;
}