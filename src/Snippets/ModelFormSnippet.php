<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Zalt\Snippets\FullDataGenericModelTrait;

/**
 * Displays an edit form based on the model the model set through the $model snippet parameter.
 *
 * This class is not in the standard snippet loading directories and does not follow
 * their naming conventions, but exists only to make it simple to extend this class
 * for a specific implementation.
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.4.4
 */
class ModelFormSnippet extends ModelFormSnippetAbstract
{
    use FullDataGenericModelTrait;
}
