<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Form;

use Gems\Snippets\ModelFormSnippet;
use Gems\Snippets\Usage\UsageSnippet;
use Gems\SnippetsActions\UsageCounterActionTrait;
use Zalt\SnippetsActions\ParameterActionInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Form
 * @since      Class available since version 1.9.2
 */
class EditAction extends CreateAction implements ParameterActionInterface
{
    use UsageCounterActionTrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ModelFormSnippet::class,
        UsageSnippet::class,
    ];

    /**
     * True when the form should edit a new model item.
     * @var boolean
     */
    public bool $createData = false;
}