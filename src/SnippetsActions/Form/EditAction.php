<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsActions\Form;

use Zalt\SnippetsActions\ParameterActionInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsActions\Form
 * @since      Class available since version 1.9.2
 */
class EditAction extends CreateAction implements ParameterActionInterface
{
    /**
     * True when the form should edit a new model item.
     * @var boolean
     */
    public bool $createData = false;
}