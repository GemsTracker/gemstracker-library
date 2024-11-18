<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Ask;

use Gems\Snippets\Ask\AskReturnSnippet;
use Gems\Tracker\Token;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ParameterActionInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @since      Class available since version 1.0
 */
class AskReturnAction extends AbstractAction implements ParameterActionInterface
{
    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        AskReturnSnippet::class,
    ];

    public ?Token $token = null;
}