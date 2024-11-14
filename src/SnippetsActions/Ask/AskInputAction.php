<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Ask;

use Gems\Snippets\Ask\AskTokenInputSnippet;
use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Zalt\SnippetsActions\Form\ZendEditAction;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @since      Class available since version 1.0
 */
class AskInputAction extends ZendEditAction
{
    use ButtonRowActiontrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        AskTokenInputSnippet::class,
        ];

    public string $clientIp = '';
}