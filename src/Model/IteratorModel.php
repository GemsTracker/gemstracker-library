<?php

namespace Gems\Model;

use MUtil\Model\ArrayModelAbstract;

class IteratorModel extends ArrayModelAbstract
{
    public function __construct(string $name, protected readonly iterable $data)
    {
        parent::__construct($name);
    }

    protected function _loadAllTraversable()
    {
        return $this->data;
    }
}