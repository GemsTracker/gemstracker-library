<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Zalt\Snippets\DataReaderGenericModelTrait;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.9.2
 */
class ModelTableSnippet extends ModelTableSnippetAbstract
{
    use DataReaderGenericModelTrait;
}