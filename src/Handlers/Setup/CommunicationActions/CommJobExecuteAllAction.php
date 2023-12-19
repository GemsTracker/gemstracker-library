<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Handlers\Setup\CommunicationActions
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Setup\CommunicationActions;

use Gems\Snippets\Communication\CommJobExecuteBatchSnippet;
use Gems\Snippets\Communication\CommJobExecuteFormSnippet;
use Gems\SnippetsActions\ContentTitleActionTrait;
use Zalt\SnippetsActions\PostActionInterface;

/**
 * @package    Gems
 * @subpackage Handlers\Setup\CommunicationActions
 * @since      Class available since version 1.0
 */
class CommJobExecuteAllAction extends \Zalt\SnippetsActions\AbstractAction implements PostActionInterface
{
    use ContentTitleActionTrait;

    const STEP_BATCH = 'batch';
    const STEP_FORM = 'form';
    const STEP_RESET = 'reset';

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        CommJobExecuteFormSnippet::class,
        CommJobExecuteBatchSnippet::class,
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

    public string $formTitle = '';

    public string $step = self::STEP_FORM;
}