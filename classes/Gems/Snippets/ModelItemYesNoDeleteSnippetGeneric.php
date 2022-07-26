<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.4
 */
class ModelItemYesNoDeleteSnippetGeneric extends \Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
    }
}
