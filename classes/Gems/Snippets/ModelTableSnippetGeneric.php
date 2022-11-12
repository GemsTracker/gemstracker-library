<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use \Zalt\Model\Data\DataReaderInterface;

/**
 * Displays multiple items from a model in a tabel by row using
 * the model set through the $model snippet parameter.
 *
 * This class is not in the standard snippet loading directories and does not follow
 * their naming conventions, but exists only to make it simple to extend this class
 * for a specific implementation.
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.4.4
 */
class ModelTableSnippetGeneric extends ModelTableSnippetAbstract
{
    /**
     *
     * @var \DataReaderInterface
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \Zalt\Model\Data\DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        return $this->model;
    }
}
