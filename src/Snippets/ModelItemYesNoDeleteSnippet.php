<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @license    New BSD License
 */

namespace Gems\Snippets;

use \Zalt\Snippets\FullDataGenericModelTrait;

/**
 * Ask Yes/No conformation for deletion and deletes item when confirmed.
 *
 * Can be used for other uses than delete by overriding performAction().
 *
 * This class is not in the standard snippet loading directories and does not follow
 * their naming conventions, but exists only to make it simple to extend this class
 * for a specific implementation.
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.4.4
 */
class ModelItemYesNoDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract
{
    use FullDataGenericModelTrait;
}
