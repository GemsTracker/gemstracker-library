<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Displays an edit form using tabs based on the model the model set through the $model snippet parameter.
 *
 * This class is not in the standard snippet loading directories and does not follow
 * their naming conventions, but exists only to make it simple to extend this class
 * for a specific implementation.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_ModelTabFormSnippetGeneric extends \Gems_Snippets_ModelFormSnippetGeneric
{
    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = true;
}