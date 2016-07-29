<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Displays an edit form based on the model the model set through the $model snippet parameter.
 *
 * This class is not in the standard snippet loading directories and does not follow
 * their naming conventions, but exists only to make it simple to extend this class
 * for a specific implementation.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class Gems_Snippets_ModelFormSnippetGeneric extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
    }
}
