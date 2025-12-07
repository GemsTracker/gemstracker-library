<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Ask;

use Gems\Snippets\Ask\ResumeLaterSnippet;
use Gems\SnippetsActions\ButtonRowActiontrait;
use Gems\Tracker\Token;
use Zalt\SnippetsActions\Form\ZendEditAction;
use Zalt\SnippetsActions\ParameterActionInterface;

/**
 * @package    Gems
 * @subpackage SnippetsActions\Ask
 * @since      Class available since version 1.0
 */
class ResumeLaterAction extends ZendEditAction implements ParameterActionInterface
{
    use ButtonRowActiontrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ResumeLaterSnippet::class,
    ];

    public string $clientIp = '';

    public ?Token $token = null;
}