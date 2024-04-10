<?php

namespace Gems\Export\Db;

use Psr\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;

class ModelContainer implements ContainerInterface
{
    protected array $models = [];

    public function __construct(
        protected readonly ProjectOverloader $projectOverloader,
    )
    {}


    public function get(string $id, array $applyFunctions = []): DataReaderInterface
    {
        if (isset($this->models[$id])) {
            return $this->models[$id];
        }

        $model = $this->projectOverloader->create($id);
        foreach($applyFunctions as $applyFunction) {
            if (method_exists($model, $applyFunction)) {
                $model->$applyFunction();
            }
        }

        $this->models[$id] = $model;
        return $this->models[$id];
    }

    public function has(string $id): bool
    {
        return true;
    }
}