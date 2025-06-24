<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Export\Db
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Export\Db;

use Psr\Container\ContainerInterface;
use Zalt\Model\MetaModellerInterface;

/**
 * @package    Gems
 * @subpackage Export\Db
 * @since      Class available since version 1.0
 */
class SingleModelContainer implements Containerinterface
{
    public function __construct(
        protected readonly MetaModellerInterface $model,
        protected readonly string $modelIdentifier,
    )
    { }


    public function get(string $id, array $modelFilter = [], array $applyFunctions = []): MetaModellerInterface
    {
        if ($id !== $this->modelIdentifier) {
            throw new \Exception("Id $id not found in " . __CLASS__ . ".");
        }

        foreach($applyFunctions as $applyFunction) {
            if (method_exists($this->model, $applyFunction)) {
                $this->model->$applyFunction();
            }
        }

        return $this->model;
    }

    public function has(string $id): bool
    {
        return $id === $this->modelIdentifier;
    }

}