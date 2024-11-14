<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Ask;

use Gems\Snippets\Generic\CurrentButtonRowSnippet;
use Gems\Snippets\Token\TokenForgottenSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Zalt\SnippetsActions\Form\ZendEditAction;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @since      Class available since version 1.0
 */
class LostTokenAction extends ZendEditAction
{
    use ButtonRowActiontrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        TokenForgottenSnippet::class,
    ];

    public string $clientIp = '';
}