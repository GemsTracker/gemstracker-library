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
use Gems\SnippetsActions\ButtonRowActiontrait;
use Zalt\Model\MetaModellerInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Form
 * @since      Class available since version 1.9.2
 */
class CreateAction extends \Zalt\SnippetsActions\Form\ZendEditAction
{
    use ButtonRowActiontrait;

    /**
     * @var array Of snippet class names
     */
    protected array $_snippets = [
        ModelFormSnippet::class,
    ];

    /**
     * True when the form should edit a new model item.
     * @var boolean
     */
    public bool $createData = true;
    
    public MetaModellerInterface $model;
}