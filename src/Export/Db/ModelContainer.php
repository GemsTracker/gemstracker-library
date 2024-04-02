<?php

namespace Gems\Export\Db;

use Psr\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;

class ModelContainer implements ContainerInterface
{
    protected array $models = [];

    public function __construct(
        protected readonly ProjectOverloader $projectOverloader,
    )
    {}


    public function get(string $id)
    {
        if (isset($this->models[$id])) {
            return $this->models[$id];
        }

        $this->models[$id] = $this->projectOverloader->create($id);
        return $this->models[$id];
    }

    public function has(string $id)
    {
        return true;
    }
}