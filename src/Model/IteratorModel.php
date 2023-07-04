<?php

namespace Gems\Model;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Ra\ArrayModelAbstract;

class IteratorModel extends ArrayModelAbstract
{
    /*public function __construct(string $name, protected readonly iterable $data)
    {
        parent::__construct($name);
    }*/
    public function __construct(MetaModelInterface $metaModel, protected iterable $data = [])
    {
        parent::__construct($metaModel);
    }

    protected function _loadAll(): array
    {
        if ($this->data instanceof \Traversable) {
            return iterator_to_array($this->data);
        }
        return $this->data;
    }

    public function setData(iterable $data): void
    {
        $this->data = $data;
    }
}
