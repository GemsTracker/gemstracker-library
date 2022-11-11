<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Zalt\Model\Data\DataReaderInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.9.2
 */
class ModelTableSnippet extends ModelTableSnippetAbstract
{
    /**
     *
     * @var \Zalt\Model\Data\DataReaderInterface
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