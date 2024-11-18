<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Ask;

use Gems\Snippets\Ask\ListTokensAskSnippet;
use Gems\Snippets\Ask\ShowAllOpenSnippet;
use Gems\Tracker\Token;
use Zalt\SnippetsActions\AbstractAction;
use Zalt\SnippetsActions\ParameterActionInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @since      Class available since version 1.0
 */
class ListTokensAction extends AbstractAction implements ParameterActionInterface
{
    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ListTokensAskSnippet::class,
    ];

    public string $clientIp = '';

    public array $defaultLoopParameters = [];

    public array $defaultLoopSnippets = [
        ShowAllOpenSnippet::class,
    ];

    public ?Token $token = null;
}